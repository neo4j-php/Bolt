<?php

namespace Bolt\connection;

/**
 * Interface IConnection
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\connection
 */
interface IConnection
{
    public function __construct(string $ip = '127.0.0.1', int $port = 7687, int $timeout = 15);

    public function connect(): bool;

    public function write(string $buffer);

    public function read(int $length = 2048): string;

    public function disconnect();
}
