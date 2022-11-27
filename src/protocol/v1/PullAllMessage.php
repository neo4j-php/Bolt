<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{
    ServerState,
    Response,
    V1,
    V2,
    V3
};
use Exception;

trait PullAllMessage
{
    /**
     * Send PULL_ALL message
     * The PULL_ALL message issues a request to stream the outstanding result back to the client, before returning to a READY state.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @throws Exception
     */
    public function pullAll(): V1|V2|V3
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x3F));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read PULL_ALL response
     * @throws Exception
     */
    protected function _pullAll(): iterable
    {
        do {
            $content = $this->read($signature);

            if ($signature == Response::SIGNATURE_SUCCESS) {
                $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_READY : ServerState::READY);
            }

            yield new Response(Response::MESSAGE_PULL_ALL, $signature, $content);
        } while ($signature == Response::SIGNATURE_RECORD);
    }
}
