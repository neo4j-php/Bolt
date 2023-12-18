<?php

namespace Bolt\protocol\v5_1;

use Bolt\enum\Message;
use Bolt\error\BoltException;
use Bolt\protocol\{V5_1, V5_2, V5_3, V5_4};

trait LogoffMessage
{
    /**
     * Send LOGOFF message
     * A LOGOFF message logs off the currently authenticated user. The connection is then ready for another LOGON message.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-logoff
     * @throws BoltException
     */
    public function logoff(): V5_1|V5_2|V5_3|V5_4
    {
        $this->write($this->packer->pack(0x6B));
        $this->pipelinedMessages[] = Message::LOGOFF;
        return $this;
    }
}
