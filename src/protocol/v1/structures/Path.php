<?php

namespace Bolt\protocol\v1\structures;

use Bolt\protocol\IStructure;

/**
 * Class Path
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-path
 * @package Bolt\protocol\v1\structures
 */
class Path implements IStructure
{
    /**
     * @var Node[]
     */
    private array $nodes;
    /**
     * @var UnboundRelationship[]
     */
    private array $rels;
    /**
     * @var int[]
     */
    private array $ids;

    /**
     * Path constructor.
     * @param Node[] $nodes
     * @param UnboundRelationship[] $rels
     * @param int[] $ids
     */
    public function __construct(array $nodes, array $rels, array $ids)
    {
        $this->nodes = $nodes;
        $this->rels = $rels;
        $this->ids = $ids;
    }

    /**
     * @return Node[]
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * list of unbound relationships
     * @return UnboundRelationship[]
     */
    public function rels(): array
    {
        return $this->rels;
    }

    /**
     * relationship id and node id to represent the path
     * @return int[]
     */
    public function ids(): array
    {
        return $this->ids;
    }

    public function __toString(): string
    {
        $obj = [
            'start' => json_decode(reset($this->nodes), true),
            'end' => json_decode(end($this->nodes), true),
            'segments' => [],
            'length' => count($this->ids) - 1
        ];

        for ($i = 0; $i < count($this->nodes) - 1; $i++) {
            $obj['segments'][] = [
                'start' => json_decode($this->nodes[$i], true),
                'relationship' => array_merge(json_decode($this->rels[$i], true), ['start' => $this->nodes[$i]->id(), 'end' => $this->nodes[$i + 1]->id()]),
                'end' => json_decode($this->nodes[$i + 1], true)
            ];
        }

        return json_encode($obj);
    }
}
