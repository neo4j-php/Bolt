<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait PullAllMessage
{
    /**
     * Send PULL_ALL message
     * The PULL_ALL message issues a request to stream the outstanding result back to the client, before returning to a READY state.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @return AProtocol|\Bolt\protocol\V1|\Bolt\protocol\V2|\Bolt\protocol\V3
     * @throws Exception
     */
    public function pullAll(): AProtocol
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x3F));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read PULL_ALL response
     * @throws Exception
     */
    protected function _pullAll(): iterable
    {
        do {
            $message = $this->read($signature);

            if ($signature == Response::SIGNATURE_SUCCESS) {
                $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
            }

            yield new Response(Response::MESSAGE_PULL_ALL, $signature, $message);
        } while ($signature == Response::SIGNATURE_RECORD);
    }
}
