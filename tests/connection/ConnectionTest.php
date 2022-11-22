<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\protocol\{
    AProtocol,
    Response,
    V4_3,
    V4_4,
    V5
};
use Bolt\connection\{
    IConnection,
    Socket,
    StreamSocket
};
use Bolt\error\ConnectionTimeoutException;
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
        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = (new Bolt($conn))->build();
        $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        $this->expectException(ConnectionTimeoutException::class);
        $protocol
            ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     * @param string $alias
     * @doesNotPerformAssertions
     */
    public function testLongNoTimeout(string $alias)
    {
        $conn = $this->getConnection($alias);
        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = (new Bolt($conn))->build();
        $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        $conn->setTimeout(200);
        $protocol
            ->run('CALL apoc.util.sleep(150000)', [], ['mode' => 'r', 'tx_timeout' => 120000])
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     * @param string $alias
     */
    public function testSecondsTimeout(string $alias)
    {
        $conn = $this->getConnection($alias);
        $conn->setTimeout(1);
        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = (new Bolt($conn))->build();
        $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        $this->expectException(ConnectionTimeoutException::class);
        $protocol
            ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     * @param string $alias
     */
    public function testTimeoutRecoverAndReset(string $alias)
    {
        $conn = $this->getConnection($alias);
        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = (new Bolt($conn))->build();
        $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            iterator_to_array(
                $protocol
                    ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
                    ->pull()
                    ->getResponse(),
                false);
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException $e) {
            $newTime = microtime(true);
            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }

        $conn->setTimeout(15.0);
        $response = $protocol
            ->reset()
            ->getResponse();

        $this->assertEquals(Response::SIGNATURE_FAILURE, $response->getSignature());
        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = (new Bolt($conn))->build();
        $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            $protocol
                ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
                ->getResponse();
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
