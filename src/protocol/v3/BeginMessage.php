<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\{ServerState, Response, V3, V4, V4_1, V4_2, V4_3, V4_4, V5};
use Exception;

trait BeginMessage
{
    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-begin
     * @param array $extra
     * @return V3|V4|V4_1|V4_2|V4_3|V4_4|V5
     * @throws Exception
     */
    public function begin(array $extra = []): V3|V4|V4_1|V4_2|V4_3|V4_4|V5
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x11, (object)$extra));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::TX_READY);
        return $this;
    }

    /**
     * Read BEGIN response
     * @throws Exception
     */
    protected function _begin(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::TX_READY);
        }

        yield new Response(Response::MESSAGE_BEGIN, $signature, $content);
    }
}
