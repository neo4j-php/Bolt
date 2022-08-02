<?php

namespace Bolt\protocol\v3;

use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Bolt\protocol\Response;
use Exception;

trait RunMessage
{
    /**
     * Send RUN message
     * The RUN message requests that a Cypher query is executed with a set of parameters and additional extra data.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @param string $query
     * @param array $parameters
     * @param array $extra
     * @return AProtocol|\Bolt\protocol\V3|\Bolt\protocol\V4|\Bolt\protocol\V4_1|\Bolt\protocol\V4_2|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4
     * @throws Exception
     */
    public function run(string $query, array $parameters = [], array $extra = []): AProtocol
    {
        $this->serverState->is(ServerState::READY, ServerState::TX_READY, ServerState::TX_STREAMING);

        $this->write($this->packer->pack(
            0x10,
            $query,
            (object)$parameters,
            (object)$extra
        ));

        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set($this->serverState->get() == ServerState::READY ? ServerState::STREAMING : ServerState::TX_STREAMING);
        return $this;
    }

    /**
     * Read RUN response
     * @throws Exception
     */
    protected function _run(): iterable
    {
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set($this->serverState->get() === ServerState::READY ? ServerState::STREAMING : ServerState::TX_STREAMING);
        }

        yield new Response(Response::MESSAGE_RUN, $signature, $message);
    }
}
