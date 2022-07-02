<?php

namespace Bolt\protocol\v3;

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
trait HelloMessage
{
    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropiate array
     * @return array
     * @throws Exception
     */
    public function hello(array $extra): array
    {
        $this->write($this->packer->pack(0x01, $extra));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            throw new MessageException($message['message'], $message['code']);
        }

        return $message;
    }
}
