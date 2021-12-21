<?php

namespace Bolt\structures;

/**
 * Class Relationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://7687.org/packstream/packstream-specification-1.html#relationship---structure
 * @package Bolt\structures
 */
class Relationship implements IStructure
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $startNodeId;
    /**
     * @var int
     */
    private $endNodeId;
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
     * @param int $id
     * @param int $startNodeId
     * @param int $endNodeId
     * @param string $type
     * @param array $properties
     */
    public function __construct(int $id, int $startNodeId, int $endNodeId, string $type, array $properties)
    {
        $this->id = $id;
        $this->startNodeId = $startNodeId;
        $this->endNodeId = $endNodeId;
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
     * @return int
     */
    public function startNodeId(): int
    {
        return $this->startNodeId;
    }

    /**
     * @return int
     */
    public function endNodeId(): int
    {
        return $this->endNodeId;
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

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'start' => $this->startNodeId,
            'end' => $this->endNodeId,
            'type' => $this->type,
            'properties' => $this->properties
        ]);
    }
}
