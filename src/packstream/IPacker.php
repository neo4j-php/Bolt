<?php

namespace Bolt\packstream;

use Bolt\error\PackException;

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
     * @param array $structuresLt [signature => classFQN]
     */
    public function __construct(array $structuresLt = []);

    /**
     * Pack message
     * @throws PackException
     */
    public function pack(int $signature, mixed ...$params): iterable;
}
