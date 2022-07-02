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
trait AckFailureMessage
{
    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---ack_failure
     * @throws Exception
     */
    private function ackFailure()
    {
        $this->write($this->packer->pack(0x0E));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            throw new MessageException($message['message'], $message['code']);
        }
    }
}
