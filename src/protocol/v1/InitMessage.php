<?php

namespace Bolt\protocol\v1;

use Bolt\connection\IConnection;
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
trait InitMessage
{
    /**
     * Send INIT message
     * The INIT message is a request for the connection to be authorized for use with the remote database.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---init
     * @param string $userAgent
     * @param array $authToken
     * @return array
     * @throws Exception
     */
    public function init(string $userAgent, array $authToken): array
    {
        $this->write($this->packer->pack(0x01, $userAgent, $authToken));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            // ..but must immediately close the connection after the failure has been sent.
            $this->connection->disconnect();
            throw new MessageException($message['message'], $message['code']);
        }

        return $message;
    }
}
