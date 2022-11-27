<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{
    ServerState,
    Response,
    V1,
    V2
};
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
    public function ackFailure(): V1|V2
    {
        $this->serverState->is(ServerState::FAILED);
        $this->write($this->packer->pack(0x0E));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::READY);
        return $this;
    }

    /**
     * Read ACK_FAILURE response
     * @throws Exception
     */
    protected function _ackFailure(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        } elseif ($signature == Response::SIGNATURE_FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }

        yield new Response(Response::MESSAGE_ACK_FAILURE, $signature, $content);
    }
}
