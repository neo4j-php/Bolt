<?php

namespace Bolt\protocol\v1;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait DiscardAllMessage
{
    /**
     * Send DISCARD_ALL message
     * The DISCARD_ALL message issues a request to discard the outstanding result and return to a READY state.
     *
     * https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @return AProtocol|\Bolt\protocol\V1|\Bolt\protocol\V2|\Bolt\protocol\V3
     * @throws Exception
     */
    public function discardAll(): AProtocol
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);
        $this->write($this->packer->pack(0x2F));
        $this->pipelinedMessages[] = __FUNCTION__;
        if ($this->serverState->get() == ServerState::STREAMING) {
            $this->serverState->set(ServerState::READY);
        }
        return $this;
    }

    /**
     * Read DISCARD_ALL response
     * @throws IgnoredException
     * @throws MessageException
     */
    protected function _discardAll(): iterable
    {
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            if (method_exists($this, 'ackFailure'))
                $this->ackFailure();
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set($this->serverState->get() === ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY);
        yield $message;
    }
}
