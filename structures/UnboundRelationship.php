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
    private $relIdentity;
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
     * @param int $relIdentity
     * @param string $type
     * @param array $properties
     */
    public function __construct(int $relIdentity, string $type, array $properties)
    {
        $this->relIdentity = $relIdentity;
        $this->type = $type;
        $this->properties = $properties;
    }

    /**
     * @return int
     */
    public function relIdentity(): int
    {
        return $this->relIdentity;
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