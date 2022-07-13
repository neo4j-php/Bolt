<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 4.1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-41
 * @package Bolt\protocol
 */
class V4_1 extends V4
{
    /**
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @inheritDoc
     */
    public function hello(...$args): array
    {
        if (isset($args[0]['routing']) && is_array($args[0]['routing']))
            $args[0]['routing'] = (object)$args[0]['routing'];

        return parent::hello(...$args);
    }
}
