<?php

namespace Bolt\packstream;

/**
 * Interface IUnpacker
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\packstream
 */
interface IUnpacker
{
    /**
     * @param array $structuresLt [signature => classFQN]
     */
    public function __construct(array $structuresLt = []);

    /**
     * Unpack message
     */
    public function unpack(string $msg): mixed;

    /**
     * Get unpacked message status signature
     */
    public function getSignature(): int;
}
