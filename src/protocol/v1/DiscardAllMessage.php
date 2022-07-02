<?php

namespace Bolt\protocol\v1;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\connection\IConnection;
use Bolt\PackStream\IPacker;
use Bolt\PackStream\IUnpacker;
use Exception;
use Generator;

/**
 * @method write(Generator $generator)
 * @method read(?int &$signature)
 * @property IPacker $packer
 * @property IUnpacker $unpacker
 * @property IConnection $connection;
 */
trait DiscardAllMessage
{
    /**
     * Send DISCARD_ALL message
     * The DISCARD_ALL message issues a request to discard the outstanding result and return to a READY state.
     *
     * https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---discard_all
     * @return array
     * @throws Exception
     */
    public function discardAll(): array
    {
        $this->write($this->packer->pack(0x2F));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            if (method_exists($this, 'ackFailure'))
                $this->ackFailure();
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('DISCARD_ALL message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}
