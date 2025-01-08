<?php

namespace Bolt\protocol\v5_4;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait TelemetryMessage
{
    /**
     * Send TELEMETRY message
     * The TELEMETRY message contains an integer representing which driver API was used.
     *
     * @link https://neo4j.com/docs/bolt/current/bolt/message/#messages-telemetry
     * @throws BoltException
     */
    public function telemetry(int $api): static
    {
        $this->write($this->packer->pack(0x54, $api));
        $this->pipelinedMessages[] = Message::TELEMETRY;
        return $this;
    }
}
