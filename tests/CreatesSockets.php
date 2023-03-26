<?php

namespace Bolt\tests;

use Bolt\connection\Socket;
use Bolt\connection\StreamSocket;
use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait CreatesSockets
{
    public function createSocket(): Socket
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        if ($GLOBALS['NEO_SSL'] ?? '' === 'true')
            $this->markTestSkipped('Sockets extension does not support SSL');

        return new Socket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687, 3);
    }

    public function createStreamSocket(): StreamSocket
    {
        $conn = new StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        if ($GLOBALS['NEO_SSL'] === true) {
            $conn->setSslContextOptions([
                'verify_peer' => true,
                'allow_self_signed' => $GLOBALS['NEO_SSL_SELF_SIGNED'] === true,
            ]);
        }

        return $conn;
    }
}