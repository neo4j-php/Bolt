<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\connection\IConnection;
use Bolt\connection\Socket;
use Bolt\connection\StreamSocket;
use Bolt\error\ConnectionTimeoutException;
use Bolt\error\MessageException;
use Bolt\helpers\Auth;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionTest
 *
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\connection\AConnection
 * @covers \Bolt\connection\Socket
 * @covers \Bolt\connection\StreamSocket
 *
 * @package Bolt\tests\connection
 * @requires PHP >= 7.1
 * @requires extension sockets
 */
final class ConnectionTest extends TestCase
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

            $this->assertGreaterThanOrEqual(1.5, $newTime - $time);
        }
    }


    /**
     * @dataProvider provideConnections
     * @doesNotPerformAssertions
     */
    public function testLongNoTimeout(string $alias): void
    {
        $socket = $this->getConnection($alias);
        $protocol = (new Bolt($socket))->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $protocol->run('SHOW ALL FUNCTIONS YIELD * WHERE name STARTS WITH "apoc." RETURN count(*)');
        $res = $protocol->pull();
        if ($res[0][0] == 0)
            $this->markTestSkipped('APOC not intalled');

        $socket->setTimeout(200);
        $protocol->run('CALL apoc.util.sleep(150000)', [], ['tx_timeout' => 150000]);
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

            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }
    }

    /**
     * @dataProvider provideConnections
     */
    public function testTimeoutRecoverAndReset(string $alias): void
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

            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }

        $socket->setTimeout(100.0);
        try {
            $protocol->reset();
        } catch (MessageException $e) {
            echo $e->getMessage();
            $protocol = (new Bolt($socket))->build();
            $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
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

            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }
    }

    private function getConnection(string $class): IConnection
    {
        return new $class($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int) ($GLOBALS['NEO_PORT'] ?? 7687), 1);
    }
}
