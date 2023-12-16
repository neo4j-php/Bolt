<?php

namespace Bolt\protocol\v4_1;

use Bolt\protocol\{V3, V4, V4_1, V4_2, V4_3, V4_4, V5};
use Bolt\error\BoltException;

trait HelloMessage
{
    use \Bolt\protocol\v3\HelloMessage {
        \Bolt\protocol\v3\HelloMessage::hello as __hello;
    }

    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropiate array
     * @throws BoltException
     */
    public function hello(array $extra): V3|V4|V4_1|V4_2|V4_3|V4_4|V5
    {
        if (isset($extra['routing']) && is_array($extra['routing']))
            $extra['routing'] = (object)$extra['routing'];

        return $this->__hello($extra);
    }
}
