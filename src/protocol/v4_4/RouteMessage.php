<?php

namespace Bolt\protocol\v4_4;

use Bolt\protocol\{ServerState, Response, V4_4, V5, V5_1, V5_2, V5_3, V5_4};
use Bolt\error\BoltException;

trait RouteMessage
{
    /**
     * Send ROUTE message
     * The ROUTE instructs the server to return the current routing table. In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-route
     * @param array $extra [db::String, imp_user::String]
     * @throws BoltException
     */
    public function route(array $routing, array $bookmarks = [], array $extra = []): V4_4|V5|V5_1|V5_2|V5_3|V5_4
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x66, (object)$routing, $bookmarks, (object)$extra));
        $this->pipelinedMessages[] = __FUNCTION__;
        return $this;
    }

    /**
     * Read ROUTE response
     * @throws BoltException
     */
    protected function _route(): iterable
    {
        $content = $this->read($signature);
        yield new Response(Response::MESSAGE_ROUTE, $signature, $content);
    }
}
