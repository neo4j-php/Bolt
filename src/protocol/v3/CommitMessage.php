<?php

namespace Bolt\protocol\v3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Exception;

trait CommitMessage
{
    /**
     * Send COMMIT message
     * The COMMIT message request that the Explicit Transaction is done.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-3.html#request-message---commit
     * @return array Current version has empty success message
     * @throws Exception
     */
    public function commit(): array
    {
        $this->serverState->is(ServerState::TX_READY);

        $this->write($this->packer->pack(0x12));
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
