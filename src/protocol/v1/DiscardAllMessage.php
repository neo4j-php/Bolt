<?php

namespace Bolt\protocol\v1;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Exception;

trait DiscardAllMessage
{
    /**
     * Send DISCARD_ALL message
     * The DISCARD_ALL message issues a request to discard the outstanding result and return to a READY state.
     *
     * https://7687.org/bolt/bolt-protocol-message-specification-1.html#request-message---discard_all
     * @return array
     * @throws Exception
     */
    public function discardAll(): array
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);

        $this->write($this->packer->pack(0x2F));
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
        return $message;
    }
}
