<?php

namespace Bolt\PackStream;

/**
 * Interface IUnpacker
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\PackStream
 */
interface IUnpacker
{
    /**
     * Unpack message
     * @param string $msg
     * @return mixed
     */
    public function unpack(string $msg);

    /**
     * Get unpacked message status signature
     * @return int
     */
    public function getSignature(): int;
}
