<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 4.4
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-4.html#version-44
 * @package Bolt\protocol
 */
class V4_4 extends V4_3
{
    /**
     * @inheritDoc
     */
    public function route(...$args): array
    {
        if (array_key_exists(2, $args))
            $args[2] = (object)$args[2];
        return parent::route(...$args);
    }
}
