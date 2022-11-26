<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Relationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-relationship
 * @package Bolt\protocol\v1\structures
 */
class Relationship implements IStructure
{
    /**
     * Relationship constructor.
     * @param int $id
     * @param int $startNodeId
     * @param int $endNodeId
     * @param string $type
     * @param array $properties
     */
    public function __construct(
        protected int    $id,
        protected int    $startNodeId,
        protected int    $endNodeId,
        protected string $type,
        protected array  $properties
    )
    {
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
