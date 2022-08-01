<?php

namespace Bolt\protocol\v3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
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
     * @return AProtocol
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

        $this->pipelinedMessages[] = 'run';
        $this->serverState->set($this->serverState->get() == ServerState::READY ? ServerState::STREAMING : ServerState::TX_STREAMING);
        return $this;
    }

    private function _run(): array
    {
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set($this->serverState->get() === ServerState::READY ? ServerState::STREAMING : ServerState::TX_STREAMING);
        return $message;
    }
}
