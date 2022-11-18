<?php

namespace Bolt\tests\packstream\v1\generators;

/**
 * Class DictionaryGenerator
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\packstream\v1\generators
 */
class DictionaryGenerator implements \Bolt\packstream\IPackDictionaryGenerator
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
        return array_values($this->array)[$this->position];
    }

    public function key()
    {
        return array_keys($this->array)[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, array_values($this->array));
    }

    public function count(): int
    {
        return count($this->array);
    }
}
