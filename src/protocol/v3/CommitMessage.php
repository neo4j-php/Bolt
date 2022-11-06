<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait CommitMessage
{
    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-commit
     * @return AProtocol|\Bolt\protocol\V3|\Bolt\protocol\V4|\Bolt\protocol\V4_1|\Bolt\protocol\V4_2|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4
     * @throws Exception
     */
    public function commit(): AProtocol
    {
        $this->serverState->is(ServerState::TX_READY, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x12));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::READY);

        return $this;
    }

    /**
     * Read COMMIT response
     * @throws Exception
     */
    protected function _commit(): iterable
    {
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        }

        yield new Response(Response::MESSAGE_COMMIT, $signature, $message);
    }
}
