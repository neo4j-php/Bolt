<?php

namespace Bolt\protocol\v1;

use Bolt\protocol\{AProtocol, ServerState, Response};
use Exception;

trait ResetMessage
{
    /**
     * Send RESET message
     * The RESET message requests that the connection should be set back to its initial READY state, as if an INIT had just successfully completed.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-reset
     * @return AProtocol|\Bolt\protocol\V1|\Bolt\protocol\V2|\Bolt\protocol\V3|\Bolt\protocol\V4|\Bolt\protocol\V4_1|\Bolt\protocol\V4_2|\Bolt\protocol\V4_3|\Bolt\protocol\V4_4
     * @throws Exception
     */
    public function reset(): AProtocol
    {
        $this->write($this->packer->pack(0x0F));
        $this->pipelinedMessages[] = __FUNCTION__;
        $this->serverState->set(ServerState::READY);
        return $this;
    }

    /**
     * Read RESET response
     * @throws Exception
     */
    protected function _reset(): iterable
    {
        $message = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::READY);
        } elseif ($signature == Response::SIGNATURE_FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }

        yield new Response(Response::MESSAGE_RESET, $signature, $message);
    }
}
