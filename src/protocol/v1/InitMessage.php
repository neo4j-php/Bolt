<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{ServerState, Response};
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
     * @return Response
     * @throws Exception
     */
    public function init(string $userAgent, array $authToken): Response
    {
        $this->serverState->is(ServerState::CONNECTED);

        $this->write($this->packer->pack(0x01, $userAgent, $authToken));
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        } elseif ($signature == Response::SIGNATURE_FAILURE) {
            // ..but must immediately close the connection after the failure has been sent.
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }

        return new Response(Response::MESSAGE_INIT, $signature, $message);
    }
}
