<?php

namespace Bolt\protocol\v3;

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
trait CommitMessage
{
    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---commit
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function commit(): array
    {
        $this->write($this->packer->pack(0x12));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('COMMIT message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}
