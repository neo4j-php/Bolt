<?php

namespace Bolt\structures;

/**
 * Class Path
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class Path
{
    /**
     * @var array
     */
    private $nodes;
    /**
     * @var array
     */
    private $rels;
    /**
     * @var array
     */
    private $ids;

    /**
     * Path constructor.
     * @param array $nodes
     * @param array $rels
     * @param array $ids
     */
    public function __construct(array $nodes, array $rels, array $ids)
    {
        $this->nodes = $nodes;
        $this->rels = $rels;
        $this->ids = $ids;
    }

    /**
     * @return array
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * list of unbound relationships
     * @return array
     */
    public function rels(): array
    {
        return $this->rels;
    }

    /**
     * relationship id and node id to represent the path
     * @return array
     */
    public function ids(): array
    {
        return $this->ids;
    }
}
