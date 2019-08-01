<?php

namespace Bolt\structures;

/**
 * Class Node
 * Immutable
 */
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

    /**
     * Node constructor.
     * @param int $identity
     * @param array $labels
     * @param array $properties
     */
    public function __construct(int $identity, array $labels, array $properties)
    {
        $this->identity = $identity;
        $this->labels = $labels;
        $this->properties = $properties;
    }

    /**
     * @return int
     */
    public function identity(): int
    {
        return $this->identity;
    }

    /**
     * @return array
     */
    public function labels(): array
    {
        return $this->labels;
    }

    /**
     * @return array
     */
    public function properties(): array
    {
        return $this->properties;
    }
}