<?php

namespace Bolt\protocol\v1;

use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Exception;

trait InitMessage
{
    /**
     * Send INIT message
     * The INIT message is a request for the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-init
     * @param string $userAgent
     * @param array $authToken
     * @return array
     * @throws Exception
     */
    public function init(string $userAgent, array $authToken): array
    {
        $this->serverState->is(ServerState::CONNECTED);

        $this->write($this->packer->pack(0x01, $userAgent, $authToken));
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            // ..but must immediately close the connection after the failure has been sent.
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }
}
