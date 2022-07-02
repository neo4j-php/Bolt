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
trait RollbackMessage
{
    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---rollback
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function rollback(): array
    {
        $this->write($this->packer->pack(0x13));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('ROLLBACK message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}
