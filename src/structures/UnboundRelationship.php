<?php

namespace Bolt\structures;

/**
 * Class UnboundRelationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class UnboundRelationship
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $properties;

    /**
     * UnboundRelationship constructor.
     * @param int $id
     * @param string $type
     * @param array $properties
     */
    public function __construct(int $id, string $type, array $properties)
    {
        $this->id = $id;
        $this->type = $type;
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
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function properties(): array
    {
        return $this->properties;
    }
}