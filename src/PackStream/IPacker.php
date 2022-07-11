<?php

namespace Bolt\PackStream;

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
     * @return iterable
     */
    public function pack($signature, ...$params): iterable;
}
