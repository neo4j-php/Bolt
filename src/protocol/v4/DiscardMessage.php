<?php

namespace Bolt\protocol\v4;

use Bolt\protocol\{ServerState, Response, V4, V4_1, V4_2, V4_3, V4_4, V5};
use Exception;

trait DiscardMessage
{
    /**
     * Send DISCARD message
     * The DISCARD message requests that the remainder of the result stream should be thrown away.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @param array $extra [n::Integer, qid::Integer]
     * @return V4|V4_1|V4_2|V4_3|V4_4|V5
     * @throws Exception
     */
    public function discard(array $extra = []): V4|V4_1|V4_2|V4_3|V4_4|V5
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x2F, $extra));

        $this->pipelinedMessages[] = __FUNCTION__;
        //we assume all records were discarded
        $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read DISCARD response
     * @throws Exception
     */
    protected function _discard(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            if ($content['has_more'] ?? false) {
                $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_STREAMING : ServerState::STREAMING);
            } else {
                $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_READY : ServerState::READY);
            }
        }

        yield new Response(Response::MESSAGE_DISCARD, $signature, $content);
    }
}
