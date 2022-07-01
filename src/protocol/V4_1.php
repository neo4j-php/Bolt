<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 4.1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-4.html#version-41
 * @package Bolt\protocol
 */
class V4_1 extends V4
{
    /**
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---41---hello
     * @link https://7687.org/bolt/bolt-protocol-message-specification-4.html#request-message---43---hello
     * @inheritDoc
     */
    public function hello(array $extra): array
    {
        if (isset($extra['routing']) && is_array($extra['routing']))
            $extra['routing'] = (object)$extra['routing'];

        return parent::hello($extra);
    }
}
