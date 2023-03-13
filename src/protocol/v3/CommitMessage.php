<?php

namespace Bolt\protocol\v3;

use Bolt\protocol\{ServerState, Response, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1};
use Bolt\error\BoltException;

trait CommitMessage
{
    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-commit
     * @throws BoltException
     */
    public function commit(): V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1
    {
        $this->serverState->is(ServerState::TX_READY, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x12));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::READY);

        return $this;
    }

    /**
     * Read COMMIT response
     * @throws BoltException
     */
    protected function _commit(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        }

        yield new Response(Response::MESSAGE_COMMIT, $signature, $content);
    }
}
