<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Node
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-node
 * @package Bolt\protocol\v1\structures
 */
class Node implements IStructure
{
    public function __construct(
        private int   $id,
        private array $labels,
        private array $properties
    )
    {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function labels(): array
    {
        return $this->labels;
    }

    public function properties(): array
    {
        return $this->properties;
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'labels' => $this->labels,
            'properties' => $this->properties
        ]);
    }
}
