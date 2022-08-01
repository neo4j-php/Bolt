<?php

namespace Bolt\protocol\v3;

use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Exception;

trait HelloMessage
{
    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropriate array
     * @return array
     * @throws Exception
     */
    public function hello(array $extra): array
    {
        $this->serverState->is(ServerState::CONNECTED);

        $this->write($this->packer->pack(0x01, $extra));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }
}
