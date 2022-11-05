<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class UnboundRelationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-unbound
 * @package Bolt\protocol\v1\structures
 */
class UnboundRelationship implements IStructure
{
    private int $id;
    private string $type;
    private array $properties;

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

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'type' => $this->type,
            'properties' => $this->properties
        ]);
    }
}
