<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\connection\Socket;
use Bolt\tests\CreatesSockets;

class SocketBench extends AbstractSocketBench
{
    use CreatesSockets;
    protected function createConnection(): Socket
    {
        return $this->createSocket();
    }
}