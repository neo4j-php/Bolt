<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\connection\IConnection;
use Bolt\connection\Socket;
use Bolt\connection\StreamSocket;
use Bolt\error\ConnectionTimeoutException;
use Bolt\error\MessageException;
use Bolt\helpers\Auth;
use Bolt\protocol\V4;
use PHPUnit\Framework\TestCase;

final class AConnectionTest extends TestCase
{
    public function provideConnections(): array
    {
        return [
            StreamSocket::class => [StreamSocket::class],
            Socket::class => [Socket::class],
        ];
    }

    /**
     * @dataProvider provideConnections
     */
    public function testMillisecondTimeout(string $alias): void
    {
        $socket = $this->getConnection($alias);
        $protocol = (new Bolt($socket))->build();
        $socket->setTimeout(1.5);
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
  MERGE (d:Day {day: i})
)');
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.5, $newTime - $time, 0.2);
        }
    }

    /**
     * @dataProvider provideConnections
     */
    public function testSecondsTimeout(string $alias): void
    {
        $socket = $this->getConnection($alias);
        $protocol = (new Bolt($socket))->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
  MERGE (d:Day {day: i})
)');
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.0, $newTime - $time, 0.2);
        }
    }

    /**
     * @dataProvider provideConnections
     */
    public function testTimeoutRecoverAndReset(string $alias): void
    {
        $socket = $this->getConnection($alias);
        /** @var V4 $protocol */
        $protocol = (new Bolt($socket))->build();
        $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
                MERGE (d:Day {day: i})
            )');
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.0, $newTime - $time, 0.2);
        }

        $socket->setTimeout(100.0);
        try {
            $protocol->reset();
        } catch (MessageException $e) {
            echo $e->getMessage();
            $protocol = (new Bolt($socket))->build();
            $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        }

        $socket->setTimeout(1.0);

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
                MERGE (d:Day {day: i})
            )');
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.0, $newTime - $time, 0.2);
        }
    }

    private function getConnection(string $class): IConnection
    {
        return new $class($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int) ($GLOBALS['NEO_PORT'] ?? 7687), 1);
    }
}
