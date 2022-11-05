<?php

namespace Bolt\tests\packstream\v1\generators;

/**
 * Class ListGenerator
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\packstream\v1\generators
 */
class ListGenerator implements \Bolt\packstream\IPackListGenerator
{
    private int $position;
    public array $array;

    public function __construct(array $data)
    {
        $this->array = $data;
        $this->position = 0;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->array[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->array);
    }

    public function count(): int
    {
        return count($this->array);
    }
}
