<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\error\BoltException;

trait DiscardAllMessage
{
    /**
     * Send DISCARD_ALL message
     * The DISCARD_ALL message issues a request to discard the outstanding result and return to a READY state.
     *
     * https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-discard
     * @throws BoltException
     */
    public function discardAll(): static
    {
        $this->write($this->packer->pack(0x2F));
        $this->pipelinedMessages[] = Message::DISCARD_ALL;
        return $this;
    }
}
