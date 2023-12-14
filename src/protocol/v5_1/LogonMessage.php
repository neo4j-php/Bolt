<?php

namespace Bolt\protocol\v5_1;

use Bolt\error\BoltException;
use Bolt\protocol\Response;
use Bolt\protocol\ServerState;

trait LogonMessage
{
    /**
     * Send LOGON message
     * A LOGON message carries an authentication request.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-logon
     * @throws BoltException
     */
    public function logon(array $auth): Response
    {
        $this->serverState->is(ServerState::AUTHENTICATION);
        $this->write($this->packer->pack(0x6A, (object)$auth));
        $content = $this->read($signature);
        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        } elseif ($signature == Response::SIGNATURE_FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }
        return new Response(Response::MESSAGE_LOGON, $signature, $content);
    }
}
