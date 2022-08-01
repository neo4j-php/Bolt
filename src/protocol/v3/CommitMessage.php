<?php

namespace Bolt\protocol\v3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait CommitMessage
{
    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-commit
     * @return AProtocol
     * @throws Exception
     */
    public function commit(): AProtocol
    {
        $this->serverState->is(ServerState::TX_READY, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x12));
        $this->pipelinedMessages[] = 'commit';
        $this->serverState->set(ServerState::READY);

        return $this;
    }

    /**
     * Read COMMIT response
     * @return array
     * @throws IgnoredException
     * @throws MessageException
     */
    private function _commit(): array
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

        $this->serverState->set(ServerState::READY);
        return $message;
    }
}
