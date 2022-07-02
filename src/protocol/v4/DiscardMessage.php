<?php

namespace Bolt\protocol\v4;

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
trait DiscardMessage
{
    /**
     * Send DISCARD message
     * The DISCARD message requests that the remainder of the result stream should be thrown away.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---discard
     * @param array $extra [n::Integer, qid::Integer]
     * @return array
     * @throws Exception
     */
    public function discard(array $extra = []): array
    {
        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x2F, $extra));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('DISCARD message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}
