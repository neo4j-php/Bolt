<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait BeginMessage
{
    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-begin
     * @param array $extra
     * @return AProtocol|\Bolt\protocol\V3|\Bolt\protocol\V4|\Bolt\protocol\V4_1|\Bolt\protocol\V4_2|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4
     * @throws Exception
     */
    public function begin(array $extra = []): AProtocol
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
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::TX_READY);
        }

        yield new Response(Response::MESSAGE_BEGIN, $signature, $message);
    }
}
