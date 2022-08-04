<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait RollbackMessage
{
    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-rollback
     * @return AProtocol|\Bolt\protocol\V3|\Bolt\protocol\V4|\Bolt\protocol\V4_1|\Bolt\protocol\V4_2|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4
     * @throws Exception
     */
    public function rollback(): AProtocol
    {
        $this->serverState->is(ServerState::TX_READY, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x13));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::READY);
        return $this;
    }

    /**
     * Read ROLLBACK response
     * @throws Exception
     */
    protected function _rollback(): iterable
    {
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        }

        yield new Response(Response::MESSAGE_ROLLBACK, $signature, $message);
    }
}
