<?php

namespace Bolt\protocol\v4_1;

use Bolt\protocol\Response;
use Exception;

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
     * @return Response
     * @throws Exception
     */
    public function hello(array $extra): Response
    {
        if (isset($extra['routing']) && is_array($extra['routing']))
            $extra['routing'] = (object)$extra['routing'];

        return $this->__hello($extra);
    }
}
