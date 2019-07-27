<?php

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

    public function __construct(array $nodes, array $relationships, array $sequence)
    {
        $this->nodes = $nodes;
        $this->relationships = $relationships;
        $this->sequence = $sequence;
    }
}