<?php

namespace Bolt\protocol\v3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait BeginMessage
{
    /**
     * Send BEGIN message
     * The BEGIN message request the creation of a new Explicit Transaction.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-begin
     * @param array $extra
     * @return AProtocol
     * @throws Exception
     */
    public function begin(array $extra = []): AProtocol
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x11, (object)$extra));
        $this->pipelinedMessages[] = 'begin';
        $this->serverState->set(ServerState::TX_READY);
        return $this;
    }

    private function _begin(): array
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

        $this->serverState->set(ServerState::TX_READY);
        return $message;
    }
}
