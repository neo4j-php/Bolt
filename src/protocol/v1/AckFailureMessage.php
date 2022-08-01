<?php

namespace Bolt\protocol\v1;

use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Exception;

trait AckFailureMessage
{
    /**
     * When requests fail on the server, the server will send the client a FAILURE message.
     * The client must acknowledge the FAILURE message by sending an ACK_FAILURE message to the server.
     * Until the server receives the ACK_FAILURE message, it will send an IGNORED message in response to any other message from the client.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-ack-failure
     * @throws Exception
     */
    private function ackFailure()
    {
        $this->write($this->packer->pack(0x0E));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
    }
}
