<?php

namespace Bolt\packstream;

use ArrayAccess, Countable;

/**
 * Class Bytes
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @link https://www.neo4j.com/docs/bolt/current/packstream/#data-type-bytes
 * @package Bolt\packstream
 */
class Bytes implements ArrayAccess, Countable
{
    private array $bytes = [];

    public function __construct(array $bytes = [])
    {
        $this->bytes = $bytes;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->bytes);
    }

    public function offsetGet($offset): ?string
    {
        return $this->bytes[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null)
            $this->bytes[] = $value;
        else
            $this->bytes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->bytes[$offset]);
    }

    public function count(): int
    {
        return count($this->bytes);
    }

    public function __toString(): string
    {
        return implode($this->bytes);
    }
}
