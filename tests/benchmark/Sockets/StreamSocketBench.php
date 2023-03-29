<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\connection\IConnection;
use Bolt\connection\StreamSocket;

class StreamSocketBench extends AbstractSocketBench
{
    protected function createConnection(): IConnection
    {
        $ip = gethostbyname($_ENV['NEO_HOST'] ?? 'localhost');

        return new StreamSocket($ip, $_ENV['NEO_PORT'] ?? 7687);
    }
}