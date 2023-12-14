<?php

namespace Bolt\protocol\v5_1;

use Bolt\enum\{Message, Signature, ServerState};
use Bolt\error\BoltException;
use Bolt\protocol\Response;

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
        if ($signature == Signature::SUCCESS) {
            $this->serverState->set(ServerState::READY);
        } elseif ($signature == Signature::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }
        return new Response(Message::LOGON, $signature, $content);
    }
}
