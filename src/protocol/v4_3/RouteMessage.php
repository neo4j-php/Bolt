<?php

namespace Bolt\protocol\v4_3;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\helpers\ServerState;
use Bolt\protocol\AProtocol;
use Exception;

trait RouteMessage
{
    /**
     * Send ROUTE message
     * The ROUTE instructs the server to return the current routing table. In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-route
     * @param array $routing
     * @param array $bookmarks
     * @param string|null $db
     * @return AProtocol|\Bolt\protocol\V4_3
     * @throws Exception
     */
    public function route(array $routing, array $bookmarks = [], ?string $db = null): AProtocol
    {
        $this->serverState->is(ServerState::READY);
        $this->write($this->packer->pack(0x66, (object)$routing, $bookmarks, $db));
        $this->pipelinedMessages[] = __FUNCTION__;
        return $this;
    }

    /**
     * Read ROUTE response
     * @throws IgnoredException
     * @throws MessageException
     */
    protected function _route(): iterable
    {
        $message = $this->read($signature);

        if ($signature === self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            $this->serverState->set(ServerState::INTERRUPTED);
            throw new IgnoredException(__FUNCTION__);
        }

        yield $message;
    }
}
