<?php

namespace Bolt\tests;

use Bolt\Bolt;
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
    private static array $table = [];

    public function createSocket(): Socket
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        if ($GLOBALS['NEO_SSL'] ?? '' === 'true')
            $this->markTestSkipped('Sockets extension does not support SSL');

        $socket = $this->simpleCreateSocket();

        return $this->routeIfNeeded($socket);
    }

    public function createStreamSocket(): StreamSocket
    {
        $conn = $this->simpleCreateStreamSocket();

        return $this->routeIfNeeded($conn);
    }

    private function routeIfNeeded(Socket|StreamSocket $conn): Socket|StreamSocket
    {
        if ($this->coalesceConfig('NEO_ROUTING_REQUIRED', default: false) === true) {
            if (self::$table === [] || self::$table['ttl'] < time()) {
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
                self::$table['servers'] = $servers;
                self::$table['ttl'] = $table->getContent()['rt']['ttl'] + time();
            }

            $address = self::$table['servers'][array_rand(self::$table['servers'])];
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
            $this->coalesceConfig('NEO_HOST', $host, '127.0.1'),
            $this->coalesceConfig('NEO_PORT', $port, 7687)
        );
        if ($this->coalesceConfig('NEO_SSL', default: false) === true) {
            $conn->setSslContextOptions([
                'verify_peer' => true,
                'allow_self_signed' => $this->coalesceConfig('NEO_SSL_SELF_SIGNED', default: false) === true,
            ]);
        }
        return $conn;
    }

    private function coalesceConfig(string $key, mixed $provided = null, mixed $default = null): mixed
    {
        return $provided ?? $GLOBALS[$key] ?? $_ENV[$key] ?? $default;
    }

    private function simpleCreateSocket(?string $host = null, ?int $port = null): Socket
    {
        return new Socket(
            $this->coalesceConfig('NEO_HOST', $host, '127.0.0.1'),
            $this->coalesceConfig('NEO_PORT', $port, 7687),
            3
        );
    }
}