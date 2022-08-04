<?php

namespace Bolt\protocol\v4;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait DiscardMessage
{
    /**
     * Send DISCARD message
     * The DISCARD message requests that the remainder of the result stream should be thrown away.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @param array $extra [n::Integer, qid::Integer]
     * @return AProtocol|\Bolt\protocol\V4|\Bolt\protocol\V4_1|\Bolt\protocol\V4_2|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4
     * @throws Exception
     */
    public function discard(array $extra = []): AProtocol
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x2F, $extra));

        $this->pipelinedMessages[] = __FUNCTION__;
        //we assume all records were discarded
        $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read DISCARD response
     * @throws Exception
     */
    protected function _discard(): iterable
    {
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            if ($message['has_more'] ?? false) {
                $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_STREAMING : ServerState::STREAMING);
            } else {
                $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
            }
        }

        yield new Response(Response::MESSAGE_DISCARD, $signature, $message);
    }
}
