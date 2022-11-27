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
use Exception;

trait RunMessage
{
    /**
     * Send RUN message
     * The RUN message requests that a Cypher query is executed with a set of parameters and additional extra data.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @throws Exception
     */
    public function run(string $query, array $parameters = [], array $extra = []): V3|V4|V4_1|V4_2|V4_3|V4_4|V5
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::STREAMING, ServerState::TX_STREAMING);

        $this->write($this->packer->pack(
            0x10,
            $query,
            (object)$parameters,
            (object)$extra
        ));

        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_STREAMING : ServerState::STREAMING);
        return $this;
    }

    /**
     * Read RUN response
     * @throws Exception
     */
    protected function _run(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(str_starts_with($this->serverState->get(), 'TX_') ? ServerState::TX_STREAMING : ServerState::STREAMING);
        }

        yield new Response(Response::MESSAGE_RUN, $signature, $content);
    }
}
