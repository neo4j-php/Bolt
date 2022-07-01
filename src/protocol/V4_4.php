<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Exception;

/**
 * Class Protocol version 4.4
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
class V4_4 extends V4_3
{
    /**
     * Send ROUTE message
     * The ROUTE instructs the server to return the current routing table. In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---44---route
     * @param array $routing
     * @param array $bookmarks
     * @param array $extra
     * @return array
     * @throws Exception
     *
     * @todo How to override method with different set of arguments? It is possible for class to extend 4.2 instead but it won't make sense. Any ideas how to solve it?
     */
    public function route(array $routing, array $bookmarks = [], array $extra = []): array
    {
        $this->write($this->packer->pack(0x66, (object)$routing, $bookmarks, (object)$extra));
        $message = $this->read($signature);

        if ($signature === self::FAILURE) {
            throw new MessageException($message['message'], $message['code']);
        }

        if ($signature == self::IGNORED) {
            throw new IgnoredException('ROUTE message IGNORED. Server in FAILED or INTERRUPTED state.');
        }

        return $message;
    }
}
