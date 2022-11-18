<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\Relationship as v1_Relationship;

/**
 * Class Relationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-relationship
 * @package Bolt\protocol\v5\structures
 */
class Relationship extends v1_Relationship
{
    private string $element_id;
    private string $start_node_element_id;
    private string $end_node_element_id;

    /**
     * @inheritDoc
     * @param string $element_id
     * @param string $start_node_element_id
     * @param string $end_node_element_id
     */
    public function __construct(int $id, int $startNodeId, int $endNodeId, string $type, array $properties, string $element_id, string $start_node_element_id, string $end_node_element_id)
    {
        parent::__construct($id, $startNodeId, $endNodeId, $type, $properties);
        $this->element_id = $element_id;
        $this->start_node_element_id = $start_node_element_id;
        $this->end_node_element_id = $end_node_element_id;
    }

    /**
     * @return string
     */
    public function element_id(): string
    {
        return $this->element_id;
    }

    /**
     * @return string
     */
    public function start_node_element_id(): string
    {
        return $this->start_node_element_id;
    }

    /**
     * @return string
     */
    public function end_node_element_id(): string
    {
        return $this->end_node_element_id;
    }

    public function __toString(): string
    {
        return json_encode([
            'identity' => $this->id(),
            'start' => $this->startNodeId(),
            'end' => $this->endNodeId(),
            'type' => $this->type(),
            'properties' => $this->properties(),
            'element_id' => $this->element_id(),
            'start_node_element_id' => $this->start_node_element_id(),
            'end_node_element_id' => $this->end_node_element_id()
        ]);
    }
}
