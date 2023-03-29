<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\connection\IConnection;
use Bolt\connection\Socket;

class SocketBench extends AbstractSocketBench
{
    protected function createConnection(): IConnection
    {
        $ip = gethostbyname($_ENV['NEO_HOST'] ?? 'localhost');

        return new Socket($ip, $_ENV['NEO_PORT'] ?? 7687);
    }
}