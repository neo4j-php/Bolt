<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Exception;

/**
 * Class Protocol version 4.3
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-4.html#version-43
 * @package Bolt\protocol
 */
class V4_3 extends V4_2
{
    /**
     * Send ROUTE message
     * The ROUTE instructs the server to return the current routing table. In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     *
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---43---route
     * @param array $routing
     * @param array $bookmarks
     * @param string|null $db
     * @return array
     * @throws Exception
     */
    public function route(array $routing, array $bookmarks = [], ?string $db = null): array
    {
        $this->write($this->packer->pack(0x66, (object)$routing, $bookmarks, $db));
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
