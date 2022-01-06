<?php

namespace Bolt\PackStream;

use Generator;

/**
 * Interface IPacker
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\PackStream
 */
interface IPacker
{
    /**
     * @param $signature
     * @param mixed ...$params
     * @return Generator
     */
    public function pack($signature, ...$params): Generator;
}
