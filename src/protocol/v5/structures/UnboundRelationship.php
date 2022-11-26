<?php

namespace Bolt\protocol\v5\structures;

use Bolt\protocol\v1\structures\UnboundRelationship as v1_UnboundRelationship;

/**
 * Class UnboundRelationship
 * Immutable
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/#structure-unbound
 * @package Bolt\protocol\v5\structures
 */
class UnboundRelationship extends v1_UnboundRelationship
{
    /**
     * @inheritDoc
     * @param string $element_id
     */
    public function __construct(
        protected int    $id,
        protected string $type,
        protected array  $properties,
        private string   $element_id
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
            'type' => $this->type(),
            'properties' => $this->properties(),
            'element_id' => $this->element_id()
        ]);
    }
}
