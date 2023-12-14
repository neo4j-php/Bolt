<?php

namespace Bolt\protocol\v5_1;

use Bolt\enum\{Message, Signature};
use Bolt\error\BoltException;
use Bolt\protocol\Response;
use Bolt\protocol\ServerState;

trait LogoffMessage
{
    /**
     * Send LOGOFF message
     * A LOGOFF message logs off the currently authenticated user. The connection is then ready for another LOGON message.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-logoff
     * @throws BoltException
     */
    public function logoff(): Response
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x6B));
        $content = $this->read($signature);
        if ($signature == Signature::SUCCESS) {
            $this->serverState->set(ServerState::AUTHENTICATION);
        } else {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }
        return new Response(Message::LOGOFF, $signature, $content);
    }
}
