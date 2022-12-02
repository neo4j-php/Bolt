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
    public function __construct(
        private int    $id,
        private int    $startNodeId,
        private int    $endNodeId,
        private string $type,
        private array  $properties
    )
    {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function startNodeId(): int
    {
        return $this->startNodeId;
    }

    public function endNodeId(): int
    {
        return $this->endNodeId;
    }

    public function type(): string
    {
        return $this->type;
    }

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
