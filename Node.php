<?php

class Node
{
    /**
     * @var int
     */
    private $identity;
    /**
     * @var array
     */
    private $labels;
    /**
     * @var array
     */
    private $properties;

    public function __construct(int $identity, array $labels, array $properties)
    {
        $this->identity = $identity;
        $this->labels = $labels;
        $this->properties = $properties;
    }
}