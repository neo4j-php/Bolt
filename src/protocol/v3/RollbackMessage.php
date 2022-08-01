<?php

namespace Bolt\protocol\v3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait RollbackMessage
{
    /**
     * Send ROLLBACK message
     * The ROLLBACK message requests that the Explicit Transaction rolls back.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-rollback
     * @return AProtocol
     * @throws Exception
     */
    public function rollback(): AProtocol
    {
        $this->serverState->is(ServerState::TX_READY, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x13));
        $this->pipelinedMessages[] = 'rollback';
        $this->serverState->set(ServerState::READY);
        return $this;
    }

    /**
     * Read ROLLBACK response
     * @return array
     * @throws IgnoredException
     * @throws MessageException
     */
    private function _rollback(): array
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
