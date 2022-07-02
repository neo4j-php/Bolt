<?php

namespace Bolt\protocol\v1;

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
trait ResetMessage
{
    /**
     * Send RESET message
     * The RESET message requests that the connection should be set back to its initial READY state, as if an INIT had just successfully completed.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---reset
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function reset(): array
    {
        $this->write($this->packer->pack(0x0F));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            throw new MessageException($message['message'], $message['code']);
        }

        return $message;
    }
}
