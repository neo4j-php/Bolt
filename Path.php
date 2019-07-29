<?php

/**
 * Class Path
 * Immutable
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
    private $relationships;
    /**
     * @var array
     */
    private $sequence;

    /**
     * Path constructor.
     * @param array $nodes
     * @param array $relationships
     * @param array $sequence
     */
    public function __construct(array $nodes, array $relationships, array $sequence)
    {
        $this->nodes = $nodes;
        $this->relationships = $relationships;
        $this->sequence = $sequence;
    }

    /**
     * @return array
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return array
     */
    public function relationships(): array
    {
        return $this->relationships;
    }

    /**
     * @return array
     */
    public function sequence(): array
    {
        return $this->sequence;
    }
}