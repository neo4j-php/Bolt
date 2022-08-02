<?php

namespace Bolt\protocol\v4;

use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;
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
        $this->serverState->is(ServerState::STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x2F, $extra));

        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set($this->serverState->get() == ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY);
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
            $this->serverState->set(($message['has_more'] ?? false) ? $this->serverState->get() : ($this->serverState->get() === ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY));
        }

        yield new Response(Response::MESSAGE_DISCARD, $signature, $message);
    }
}
