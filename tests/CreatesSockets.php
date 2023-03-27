<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\AConnection;
use Bolt\connection\Socket;
use Bolt\connection\StreamSocket;
use Bolt\helpers\Auth;
use Bolt\protocol\V4_4;
use Bolt\protocol\V5;
use Bolt\protocol\V5_1;
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

        $socket = $this->simpleCreateSocket();

        return $this->routeIfNeeded($socket);
    }

    public function createStreamSocket(bool $route = true): StreamSocket
    {
        $conn = $this->simpleCreateStreamSocket();

        return $route ? $this->routeIfNeeded($conn) : $conn;
    }

    private function routeIfNeeded(Socket|StreamSocket $conn): Socket|StreamSocket
    {
        if (($GLOBALS['NEO_ROUTING_REQUIRED'] ?? '') === true) {
            /** @var V4_4|V5|V5_1 $protocol */
            $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
            $protocol->hello(Auth::basic(
                $GLOBALS['NEO_USER'],
                $GLOBALS['NEO_PASS']
            ));
            $table = $protocol->route([])->getResponse();

            $servers = [];
            foreach ($table->getContent()['rt']['servers'] as $server) {
                if ($server['role'] === 'WRITE') {
                    $servers = array_merge($servers, $server['addresses']);
                }
            }

            $address = $servers[array_rand($servers)];
            [$host, $port] = explode(':', $address);
            if ($conn instanceof Socket) {
                return $this->simpleCreateSocket($host, $port);
            }
            return $this->simpleCreateStreamSocket($host, $port);
        }

        return $conn;
    }

    /**
     * @return StreamSocket
     */
    private function simpleCreateStreamSocket(?string $host = null, ?int $port = null): StreamSocket
    {
        $conn = new StreamSocket(
            $host ?? $GLOBALS['NEO_HOST'] ?? '127.0.0.1',
            $port ?? $GLOBALS['NEO_PORT'] ?? 7687
        );
        if ($GLOBALS['NEO_SSL'] === true) {
            $conn->setSslContextOptions([
                'verify_peer' => true,
                'allow_self_signed' => $GLOBALS['NEO_SSL_SELF_SIGNED'] === true,
            ]);
        }
        return $conn;
    }

    private function simpleCreateSocket(?string $host = null, ?int $port = null): Socket
    {
        return new Socket(
            $host ?? $GLOBALS['NEO_HOST'] ?? '127.0.0.1',
            $port ?? $GLOBALS['NEO_PORT'] ?? 7687,
            3
        );
    }
}