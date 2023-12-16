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
     * @param Node[] $nodes
     * @param UnboundRelationship[] $rels list of unbound relationships
     * @param int[] $ids relationship id and node id to represent the path
     */
    public function __construct(
        public readonly array $nodes,
        public readonly array $rels,
        public readonly array $ids
    )
    {
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
                'relationship' => array_merge(json_decode($this->rels[$i], true), ['start' => $this->nodes[$i]->id, 'end' => $this->nodes[$i + 1]->id()]),
                'end' => json_decode($this->nodes[$i + 1], true)
            ];
        }

        return json_encode($obj);
    }
}
