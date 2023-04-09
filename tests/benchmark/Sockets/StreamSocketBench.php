<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\connection\IConnection;
use Bolt\tests\CreatesSockets;

class StreamSocketBench extends AbstractSocketBench
{
    use CreatesSockets;

    protected function createConnection(): IConnection
    {
        return $this->createStreamSocket();
    }
}