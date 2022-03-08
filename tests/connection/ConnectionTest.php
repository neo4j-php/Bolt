<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\connection\{
    IConnection,
    Socket,
    StreamSocket
};
use Bolt\error\{
    ConnectionTimeoutException,
    MessageException
};
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
     * @param string $alias
     */
    public function testMillisecondTimeout(string $alias)
    {
        $conn = $this->getConnection($alias);
        $conn->setTimeout(1.5);
        $protocol = (new Bolt($conn))->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        $this->expectException(ConnectionTimeoutException::class);
        $protocol->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )');
    }

    /**
     * @dataProvider provideConnections
     * @param string $alias
     * @doesNotPerformAssertions
     */
    public function testLongNoTimeout(string $alias)
    {
        $conn = $this->getConnection($alias);
        $protocol = (new Bolt($conn))->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        $conn->setTimeout(200);
        $protocol->run('CALL apoc.util.sleep(150000)', [], ['mode' => 'r', 'tx_timeout' => 120000]);
    }

    /**
     * @dataProvider provideConnections
     * @param string $alias
     */
    public function testSecondsTimeout(string $alias)
    {
        $conn = $this->getConnection($alias);
        $conn->setTimeout(1);
        $protocol = (new Bolt($conn))->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        $this->expectException(ConnectionTimeoutException::class);
        $protocol->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )');
    }

    /**
     * @dataProvider provideConnections
     * @param string $alias
     */
    public function testTimeoutRecoverAndReset(string $alias)
    {
        $conn = $this->getConnection($alias);
        $protocol = (new Bolt($conn))->build();
        $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )');
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException $e) {
            $newTime = microtime(true);
            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }

        $conn->setTimeout(15.0);
        try {
            $protocol->reset();
        } catch (MessageException $e) {
            $protocol = (new Bolt($conn))->build();
            $protocol->init(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        }

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )');
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException $e) {
            $newTime = microtime(true);
            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }
    }

    private function getConnection(string $class): IConnection
    {
        return new $class($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int)($GLOBALS['NEO_PORT'] ?? 7687), 1);
    }
}
