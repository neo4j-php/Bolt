<?php

class Relationship
{
    /**
     * @var int
     */
    private $identity;
    /**
     * @var int
     */
    private $startNodeIdentity;
    /**
     * @var int
     */
    private $endNodeIdentity;
    /**
     * @var string
     */
    private $type;
    /**
     * @var array
     */
    private $properties;

    public function __construct(int $identity, int $startNodeIdentity, int $endNodeIdentity, string $type, array $properties)
    {
        $this->identity = $identity;
        $this->startNodeIdentity = $startNodeIdentity;
        $this->endNodeIdentity = $endNodeIdentity;
        $this->type = $type;
        $this->properties = $properties;
    }
}