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
    private int $id;
    private array $labels;
    private array $properties;

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

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id,
            'labels' => $this->labels,
            'properties' => $this->properties
        ]);
    }
}
