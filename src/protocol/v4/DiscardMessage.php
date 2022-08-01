<?php

namespace Bolt\protocol\v4;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait DiscardMessage
{
    /**
     * Send DISCARD message
     * The DISCARD message requests that the remainder of the result stream should be thrown away.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @param array $extra [n::Integer, qid::Integer]
     * @return AProtocol
     * @throws Exception
     */
    public function discard(array $extra = []): AProtocol
    {
        $this->serverState->is(ServerState::STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x2F, $extra));

        $this->pipelinedMessages[] = 'discard';
        $this->serverState->set($this->serverState->get() == ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY);
        return $this;
    }

    private function _discard(): array
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

        $this->serverState->set(($message['has_more'] ?? false) ? $this->serverState->get() : ($this->serverState->get() === ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY));
        return $message;
    }
}
