<?php

namespace Bolt\packstream;

/**
 * Interface IPacker
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\packstream
 */
interface IPacker
{
    /**
     * @param int $signature
     * @param mixed ...$params
     * @return iterable
     */
    public function pack(int $signature, mixed ...$params): iterable;

    /**
     * @param array $structures [signature => classFQN]
     */
    public function setAvailableStructures(array $structures): void;
}
