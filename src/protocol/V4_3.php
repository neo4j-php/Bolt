<?php

namespace Bolt\protocol;

use Bolt\error\IgnoredException;
use Bolt\error\MessageException;
use Bolt\error\PackException;
use Exception;

/**
 * Class Protocol version 4.3
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-43
 * @package Bolt\protocol
 */
class V4_3 extends V4_2
{
    /**
     * Send ROUTE message
     * The ROUTE instructs the server to return the current routing table. In previous versions there was no explicit message for this and a procedure had to be invoked using Cypher through the RUN and PULL messages.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-route
     * @param array|string|null ...$args
     * @return array
     * @throws Exception
     */
    public function route(...$args): array
    {
        if (count($args) !== 3) {
            throw new PackException('Wrong arguments count');
        }

        return $this->io(Signatures::ROUTE, (object)$args[0], $args[1], $args[2]);
    }
}
