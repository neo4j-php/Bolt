<?php

namespace Bolt\structures;

/**
 * Class Relationship
 * Immutable
 */
class Relationship
{
    /**
     * @var int
     */
    private $identity;
    /**
     * @var int
     */
    private $startNodeIdentity;
    /**
     * @var int
     */
    private $endNodeIdentity;
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $properties;

    /**
     * Relationship constructor.
     * @param int $identity
     * @param int $startNodeIdentity
     * @param int $endNodeIdentity
     * @param string $type
     * @param array $properties
     */
    public function __construct(int $identity, int $startNodeIdentity, int $endNodeIdentity, string $type, array $properties)
    {
        $this->identity = $identity;
        $this->startNodeIdentity = $startNodeIdentity;
        $this->endNodeIdentity = $endNodeIdentity;
        $this->type = $type;
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
     * @return int
     */
    public function startNodeIdentity(): int
    {
        return $this->startNodeIdentity;
    }

    /**
     * @return int
     */
    public function endNodeIdentity(): int
    {
        return $this->endNodeIdentity;
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