<?php

namespace Bolt\protocol\v5_1;

use Bolt\protocol\{ServerState, Response};
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
    public function hello(array $extra = []): Response
    {
        $this->serverState->is(ServerState::NEGOTIATION);

        if (empty($extra['user_agent']))
            $extra['user_agent'] = \Bolt\helpers\Auth::$defaultUserAgent;
        if (isset($extra['routing']) && is_array($extra['routing']))
            $extra['routing'] = (object)$extra['routing'];

        $this->write($this->packer->pack(0x01, (object)$extra));
        $content = $this->read($signature);

        if ($signature == Response::SIGNATURE_SUCCESS) {
            $this->serverState->set(ServerState::AUTHENTICATION);
        } elseif ($signature == Response::SIGNATURE_FAILURE) {
            $this->connection->disconnect();
            $this->serverState->set(ServerState::DEFUNCT);
        }

        return new Response(Response::MESSAGE_HELLO, $signature, $content);
    }
}
