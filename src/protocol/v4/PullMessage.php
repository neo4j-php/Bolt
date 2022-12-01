<?php

namespace Bolt\protocol\v4;

use Bolt\protocol\{
    ServerState,
    Response,
    V4,
    V4_1,
    V4_2,
    V4_3,
    V4_4,
    V5
};
use Bolt\error\BoltException;

trait PullMessage
{
    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @param array $extra [n::Integer, qid::Integer]
     * @throws BoltException
     */
    public function pull(array $extra = []): V4|V4_1|V4_2|V4_3|V4_4|V5
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x3F, $extra));

        $this->pipelinedMessages[] = __FUNCTION__;
        //we assume all records were pulled
        $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read PULL response
     * @return array
     * @throws BoltException
     */
    protected function _pull(): iterable
    {
        do {
            $content = $this->read($signature);

            if ($signature == Response::SIGNATURE_SUCCESS) {
                if ($content['has_more'] ?? false) {
                    $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_STREAMING : ServerState::STREAMING);
                } else {
                    $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_READY : ServerState::READY);
                }
            }

            yield new Response(Response::MESSAGE_PULL, $signature, $content);
        } while ($signature == Response::SIGNATURE_RECORD);
    }
}
