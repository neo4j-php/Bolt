<?php

namespace Bolt\protocol\v1;

use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait ResetMessage
{
    /**
     * Send RESET message
     * The RESET message requests that the connection should be set back to its initial READY state, as if an INIT had just successfully completed.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-reset
     * @return AProtocol
     * @throws Exception
     */
    public function reset(): AProtocol
    {
        $this->write($this->packer->pack(0x0F));
        $this->pipelinedMessages[] = 'reset';
        $this->serverState->set(ServerState::READY);
        return $this;
    }

    /**
     * Read RESET response
     * @return array
     * @throws MessageException
     */
    private function _reset(): array
    {
        $message = $this->read($signature);

        if ($signature == self::FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
            throw new MessageException($message['message'], $message['code']);
        }

        $this->serverState->set(ServerState::READY);
        return $message;
    }
}
