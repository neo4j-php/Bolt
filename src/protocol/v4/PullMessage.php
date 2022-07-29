<?php

namespace Bolt\protocol\v4;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Exception;

trait PullMessage
{
    /**
     * Send PULL message
     * The PULL message requests data from the remainder of the result stream.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---pull
     * @param array $extra [n::Integer, qid::Integer]
     * @return array
     * @throws Exception
     */
    public function pull(array $extra = []): array
    {
        $this->serverState->is(ServerState::STREAMING, ServerState::TX_STREAMING);

        if (!array_key_exists('n', $extra))
            $extra['n'] = -1;

        $this->write($this->packer->pack(0x3F, $extra));

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
