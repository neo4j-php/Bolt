<?php

namespace Bolt\tests\benchmark\Sockets;

use Bolt\connection\StreamSocket;
use Bolt\tests\CreatesSockets;

class StreamSocketBench extends AbstractSocketBench
{
    use CreatesSockets;

    protected function createConnection(): StreamSocket
    {
        return $this->createStreamSocket();
    }
}