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

trait PullAllMessage
{
    /**
     * Send PULL_ALL message
     * The PULL_ALL message issues a request to stream the outstanding result back to the client, before returning to a READY state.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @throws BoltException
     */
    public function pullAll(): V1|V2|V3
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x3F));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read PULL_ALL response
     * @throws BoltException
     */
    protected function _pullAll(): iterable
    {
        do {
            $content = $this->read($signature);

            if ($signature == Signature::SUCCESS) {
                $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_READY : ServerState::READY);
            }

            yield new Response(Message::PULL_ALL, $signature, $content);
        } while ($signature == Signature::RECORD);
    }
}
