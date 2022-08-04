<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait RunMessage
{
    /**
     * Send RUN message
     * A RUN message submits a new query for execution, the result of which will be consumed by a subsequent message, such as PULL_ALL.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @param string $query
     * @param array $parameters
     * @return AProtocol|\Bolt\protocol\V1|\Bolt\protocol\V2
     * @throws Exception
     */
    public function run(string $query, array $parameters = []): AProtocol
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x10, $query, (object)$parameters));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::STREAMING);
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
            $this->serverState->set(ServerState::STREAMING);
        }

        yield new Response(Response::MESSAGE_RUN, $signature, $message);
    }
}
