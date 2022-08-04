<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\{ServerState, Response};
use Exception;

trait HelloMessage
{
    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropriate array
     * @return Response
     * @throws Exception
     */
    public function hello(array $extra): Response
    {
        $this->serverState->is(ServerState::CONNECTED);

        $this->write($this->packer->pack(0x01, $extra));
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        } elseif ($signature == Response::SIGNATURE_FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }

        return new Response(Response::MESSAGE_HELLO, $signature, $message);
    }
}
