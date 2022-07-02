<?php

namespace Bolt\protocol\v1;

use Bolt\connection\IConnection;
use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
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
trait PullAllMessage
{
    /**
     * Send PULL_ALL message
     * The PULL_ALL message issues a request to stream the outstanding result back to the client, before returning to a READY state.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---pull_all
     * @return array Array of records with last success entry
     * @throws Exception
     */
    public function pullAll(): array
    {
        $this->write($this->packer->pack(0x3F));

        $output = [];
        do {
            $message = $this->read($signature);
            $output[] = $message;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            if (method_exists($this, 'ackFailure'))
                $this->ackFailure();
            $last = array_pop($output);
            throw new MessageException($last['message'], $last['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('PULL_ALL message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $output;
    }
}
