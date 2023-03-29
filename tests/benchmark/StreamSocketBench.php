<?php

namespace Bolt\tests\benchmark;

use Bolt\connection\StreamSocket;

class StreamSocketBench
{
    private StreamSocket $socket;

    public function __construct()
    {
        $ip = gethostbyname($_ENV['NEO_HOST'] ?? 'localhost');
        $this->socket = new StreamSocket($ip, $_ENV['NEO_PORT'] ?? 7687);
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchConnect(): void
    {
        $this->socket->connect();
    }
}