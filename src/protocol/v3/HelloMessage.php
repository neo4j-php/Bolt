<?php

namespace Bolt\protocol\v3;

use Bolt\enum\Message;
use Bolt\protocol\{V3, V4, V4_1, V4_2, V4_3, V4_4, V5};
use Bolt\error\BoltException;

trait HelloMessage
{
    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropriate array
     * @throws BoltException
     */
    public function hello(array $extra): V3|V4|V4_1|V4_2|V4_3|V4_4|V5
    {
        $this->write($this->packer->pack(0x01, $extra));
        $this->pipelinedMessages[] = Message::HELLO;
        return $this;
    }
}
