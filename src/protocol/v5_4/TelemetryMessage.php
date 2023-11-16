<?php

namespace Bolt\protocol\v5_4;

use Bolt\error\BoltException;
use Bolt\protocol\Response;
use Bolt\protocol\ServerState;

trait TelemetryMessage
{
    /**
     * Send TELEMETRY message
     * The TELEMETRY message contains an integer representing which driver API was used.
     *
     * @link https://neo4j.com/docs/bolt/current/bolt/message/#messages-telemetry
     * @param int $api
     * @return Response
     * @throws BoltException
     */
    public function telemetry(int $api): Response
    {
        $this->write($this->packer->pack(0x54, $api));
        $content = $this->read($signature);

        if ($signature === Response::SIGNATURE_FAILURE) {
            $this->serverState->set(ServerState::FAILED);
        }

        return new Response(Response::MESSAGE_TELEMETRY, $signature, $content);
    }
}
