<?php

namespace Bolt\protocol\v4;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait PullMessage
{
    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @param array $extra [n::Integer, qid::Integer]
     * @return AProtocol|\Bolt\protocol\V4|\Bolt\protocol\V4_1|\Bolt\protocol\V4_2|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4
     * @throws Exception
     */
    public function pull(array $extra = []): AProtocol
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x3F, $extra));

        $this->pipelinedMessages[] = __FUNCTION__;
        //we assume all records were pulled
        $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
        return $this;
    }

    /**
     * Read PULL response
     * @return array
     * @throws Exception
     */
    protected function _pull(): iterable
    {
        do {
            $message = $this->read($signature);

            if ($signature == Response::SIGNATURE_SUCCESS) {
                if ($message['has_more'] ?? false) {
                    $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_STREAMING : ServerState::STREAMING);
                } else {
                    $this->serverState->set(substr($this->serverState->get(), 0, 3) == 'TX_' ? ServerState::TX_READY : ServerState::READY);
                }
            }

            yield new Response(Response::MESSAGE_PULL, $signature, $message);
        } while ($signature == Response::SIGNATURE_RECORD);
    }
}
