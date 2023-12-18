<?php

namespace Bolt\protocol\v1;

use Bolt\enum\Message;
use Bolt\protocol\{V1, V2, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait ResetMessage
{
    /**
     * Send RESET message
     * The RESET message requests that the connection should be set back to its initial READY state, as if an INIT had just successfully completed.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-reset
     * @throws BoltException
     */
    public function reset(): V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        $this->write($this->packer->pack(0x0F));
        $this->pipelinedMessages[] = Message::RESET;
        return $this;
    }
}
