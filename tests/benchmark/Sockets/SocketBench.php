<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\connection\IConnection;
use Bolt\connection\Socket;
use Bolt\tests\CreatesSockets;

class SocketBench extends AbstractSocketBench
{
    use CreatesSockets;
    protected function createConnection(): IConnection
    {
        return $this->createSocket();
    }
}