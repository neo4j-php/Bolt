<?php

namespace Bolt\structures;

/**
 * Class Node
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class Node
{
    /**
     * @var int
     */
    private $id;
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
     * @param int $id
     * @param array $labels
     * @param array $properties
     */
    public function __construct(int $id, array $labels, array $properties)
    {
        $this->id = $id;
        $this->labels = $labels;
        $this->properties = $properties;
    }

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->id;
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
    
    /**
     * @return string
     */
    public function property($property): string
    {
        if(isset($this->properties[$property])) {
            return $this->properties[$property];
        }
    }
}
