<?php

namespace Bolt\protocol\v4;

use Bolt\enum\{Message, Signature, ServerState};
use Bolt\protocol\{Response, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
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
    public function pull(array $extra = []): V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x3F, $extra));

        $this->pipelinedMessages[] = __FUNCTION__;
        //we assume all records were pulled
        $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_READY : ServerState::READY);
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

            if ($signature == Signature::SUCCESS) {
                if ($content['has_more'] ?? false) {
                    $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_STREAMING : ServerState::STREAMING);
                } else {
                    $this->serverState->set(in_array($this->serverState->get(), [ServerState::TX_READY, ServerState::TX_STREAMING]) ? ServerState::TX_READY : ServerState::READY);
                }
            }

            yield new Response(Message::PULL, $signature, $content);
        } while ($signature == Signature::RECORD);
    }
}
