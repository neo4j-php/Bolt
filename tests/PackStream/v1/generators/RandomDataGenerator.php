<?php

namespace Bolt\tests\PackStream\v1\generators;

use Bolt\PackStream\IPackListGenerator;

/**
 * Class ListGenerator
 * @author Ghlen Nagels
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\PackStream\v1\generators
 */
class RandomDataGenerator implements IPackListGenerator
{
    private int $rows;
    private int $count = 0;

    public function __construct(int $rows)
    {
        $this->rows = $rows;
    }

    public function current(): array
    {
        return [bin2hex(random_bytes(0x20)) => bin2hex(random_bytes(0x800))];
    }

    public function next(): void
    {
        ++$this->count;
    }

    public function key(): int
    {
        return $this->count;
    }

    public function valid(): bool
    {
        return $this->count < $this->rows;
    }

    public function rewind(): void
    {
        $this->count = 0;
    }

    public function count(): int
    {
        return $this->rows;
    }
}
