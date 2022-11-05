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
    private string $element_id;

    /**
     * @inheritDoc
     * @param string $element_id
     */
    public function __construct(int $id, array $labels, array $properties, string $element_id)
    {
        parent::__construct($id, $labels, $properties);
        $this->element_id = $element_id;
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
