<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\Node as v1_Node;

/**
 * Class Node
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-node
 * @package Bolt\protocol\v5\structures
 */
class Node extends v1_Node
{
    /**
     * @inheritDoc
     * @param string $element_id
     */
    public function __construct(
        protected int   $id,
        protected array $labels,
        protected array $properties,
        private string  $element_id
    )
    {
    }

    /**
     * @return string
     */
    public function element_id(): string
    {
        return $this->element_id;
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id(),
            'labels' => $this->labels(),
            'properties' => $this->properties(),
            'element_id' => $this->element_id()
        ]);
    }
}
