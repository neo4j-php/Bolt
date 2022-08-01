<?php

namespace Bolt\protocol\v4;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait PullMessage
{
    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#message-pull
     * @param array $extra [n::Integer, qid::Integer]
     * @return AProtocol
     * @throws Exception
     */
    public function pull(array $extra = []): AProtocol
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x3F, $extra));

        $this->pipelinedMessages[] = 'pull';
        $this->serverState->set($this->serverState->get() == ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY);
        return $this;
    }

    /**
     * Read PULL response
     * @return array
     * @throws IgnoredException
     * @throws MessageException
     */
    private function _pull(): array
    {
        $output = [];
        do {
            $message = $this->read($signature);
            $output[] = $message;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $this->serverState->set(ServerState::FAILED);
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        $this->serverState->set(($message['has_more'] ?? false) ? $this->serverState->get() : ($this->serverState->get() === ServerState::STREAMING ? ServerState::READY : ServerState::TX_READY));
        return $output;
    }
}
