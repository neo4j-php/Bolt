<?php

namespace Bolt\connection;

/**
 * Interface IConnection
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
interface IConnection
{
    /**
     * @param string $ip
     * @param int $port
     * @param int|float $timeout
     */
    public function __construct(string $ip = '127.0.0.1', int $port = 7687, $timeout = 15);

    public function connect(): bool;

    public function write(string $buffer);

    public function read(int $length = 2048): string;

    public function disconnect();

    public function getIp(): string;

    public function getPort(): int;

    public function getTimeout(): float;

    public function setTimeout(float $timeout): void;
}
