<?php

namespace Bolt\protocol\v3;

use Bolt\enum\{Message, Signature, ServerState};
use Bolt\protocol\{Response, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait BeginMessage
{
    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-begin
     * @throws BoltException
     */
    public function begin(array $extra = []): V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x11, (object)$extra));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::TX_READY);
        return $this;
    }

    /**
     * Read BEGIN response
     * @throws BoltException
     */
    protected function _begin(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Signature::SUCCESS) {
            $this->serverState->set(ServerState::TX_READY);
        }

        yield new Response(Message::BEGIN, $signature, $content);
    }
}
