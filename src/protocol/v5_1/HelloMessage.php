<?php

namespace Bolt\protocol\v5_1;

use Bolt\enum\Message;
use Bolt\protocol\{V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait HelloMessage
{
    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @throws BoltException
     */
    public function hello(array $extra = []): V5_1|V5_2|V5_3|V5_4
    {
        if (empty($extra['user_agent']))
            $extra['user_agent'] = 'bolt-php';
        if (isset($extra['routing']) && is_array($extra['routing']))
            $extra['routing'] = (object)$extra['routing'];

        $this->write($this->packer->pack(0x01, (object)$extra));
        $this->pipelinedMessages[] = Message::HELLO;
        return $this;
    }
}
