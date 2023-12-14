<?php

namespace Bolt\protocol\v1;

use Bolt\enum\{Message, Signature, ServerState};
use Bolt\protocol\{
    Response,
    V1,
    V2,
    V3
};
use Bolt\error\BoltException;

trait DiscardAllMessage
{
    /**
     * Send DISCARD_ALL message
     * The DISCARD_ALL message issues a request to discard the outstanding result and return to a READY state.
     *
     * https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @throws BoltException
     */
    public function discardAll(): V1|V2|V3
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x2F));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read DISCARD_ALL response
     * @throws BoltException
     */
    protected function _discardAll(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Signature::SUCCESS) {
            $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_READY : ServerState::READY);
        }

        yield new Response(Message::DISCARD_ALL, $signature, $content);
    }
}
