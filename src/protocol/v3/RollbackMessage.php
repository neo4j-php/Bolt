<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\{
    ServerState,
    Response,
    V3,
    V4,
    V4_1,
    V4_2,
    V4_3,
    V4_4,
    V5
};
use Bolt\error\BoltException;

trait RollbackMessage
{
    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-rollback
     * @throws BoltException
     */
    public function rollback(): V3|V4|V4_1|V4_2|V4_3|V4_4|V5
    {
        $this->serverState->is(ServerState::TX_READY, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x13));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::READY);
        return $this;
    }

    /**
     * Read ROLLBACK response
     * @throws BoltException
     */
    protected function _rollback(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        }

        yield new Response(Response::MESSAGE_ROLLBACK, $signature, $content);
    }
}
