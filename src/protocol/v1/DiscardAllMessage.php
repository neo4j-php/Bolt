<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\protocol\{V1, V2, V3};
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
    public function discardAll(): V1|V2|V3
    {
        $this->write($this->packer->pack(0x2F));
        $this->pipelinedMessages[] = Message::DISCARD_ALL;
        return $this;
    }
}
