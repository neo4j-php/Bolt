<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{
    ServerState,
    Response,
    V1,
    V2
};
use Bolt\error\BoltException;

trait RunMessage
{
    /**
     * Send RUN message
     * A RUN message submits a new query for execution, the result of which will be consumed by a subsequent message, such as PULL_ALL.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-run
     * @throws BoltException
     */
    public function run(string $query, array $parameters = []): V1|V2
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x10, $query, (object)$parameters));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::STREAMING);
        return $this;
    }

    /**
     * Read RUN response
     * @throws BoltException
     */
    protected function _run(): iterable
    {
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::STREAMING);
        }

        yield new Response(Response::MESSAGE_RUN, $signature, $content);
    }
}
