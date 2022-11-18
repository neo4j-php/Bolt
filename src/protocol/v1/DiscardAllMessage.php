<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait DiscardAllMessage
{
    /**
     * Send DISCARD_ALL message
     * The DISCARD_ALL message issues a request to discard the outstanding result and return to a READY state.
     *
     * https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @return AProtocol|\Bolt\protocol\V1|\Bolt\protocol\V2|\Bolt\protocol\V3
     * @throws Exception
     */
    public function discardAll(): AProtocol
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x2F));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read DISCARD_ALL response
     * @throws Exception
     */
    protected function _discardAll(): iterable
    {
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
        }

        yield new Response(Response::MESSAGE_DISCARD_ALL, $signature, $message);
    }
}
