<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\protocol\{AProtocol, Response, V4_4, V5, V5_1};
use Bolt\tests\ATest;
use Bolt\tests\CreatesSockets;
use Bolt\connection\{
    IConnection,
    Socket,
    StreamSocket
};
use Bolt\error\ConnectionTimeoutException;

/**
 * Class ConnectionTest
 *
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\connection
 */
final class ConnectionTest extends ATest
{
    use CreatesSockets;

    public function provideConnections(): array
    {
        return [
            StreamSocket::class => [[$this, 'createStreamSocket']],
            Socket::class => [[$this, 'createSocket']],
        ];
    }

    /**
     * @dataProvider provideConnections
     */
    public function testPersistenceAcceptance(array $factory): void
    {
        $conn = $this->getConnection($factory);
        $conn->keepAlive();

        // Force the persistent connection to refresh
        $conn->connect();
        $conn->disconnect();

        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        unset ($conn);
        unset ($protocol);

        $conn = $this->getConnection($factory);

        $conn->keepAlive();

        $protocol = (new Bolt($conn))->build();
        $response = $this->basicRun($protocol, 'RETURN 1 as one');
        self::assertEquals([
            [1]
        ], $response);
    }

    /**
     * @dataProvider provideConnections
     *
     * @runInSeparateProcess
     */
    public function testSetupPersistenceInSeparateProcess(array $factory): void
    {

        $conn = $this->getConnection($factory);
        $conn->keepAlive();

        // Force the persistent connection to refresh
        $conn->connect();
        $conn->disconnect();

        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
    }

    /**
     * @dataProvider provideConnections
     *
     * @depends testSetupPersistenceInSeparateProcess
     *
     * @runInSeparateProcess
     */
    public function testPersistenceInSeparateProcess(array $factory): void
    {
        $conn = $this->getConnection($factory);

        $conn->keepAlive();

        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->assertContains($protocol->serverState->get(), ['READY', 'CONNECTED']);
        if ($protocol->serverState->get() === 'CONNECTED') {
            $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        }
        $response = $this->basicRun($protocol, 'RETURN 1 as one');
        self::assertEquals([
            [1]
        ], $response);
    }

    /**
     * @dataProvider provideConnections
     */
    public function testPersistence(array $factory): void
    {
        $conn = $this->getConnection($factory);
        $conn->keepAlive();

        $conn->connect();

        $conn2 = $this->getConnection($factory);
        $conn2->keepAlive();

        $conn2->connect();

        $this->assertEquals($conn->getId(), $conn2->getId());
    }

    /**
     * @dataProvider provideConnections
     */
    public function testMillisecondTimeout(array $factory): void
    {
        $conn = $this->getConnection($factory);
        $conn->setTimeout(1.5);
        /** @var AProtocol|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $this->expectException(ConnectionTimeoutException::class);
        $protocol
            ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     */
    public function testLongNoTimeout(array $factory): void
    {
        $conn = $this->getConnection($factory);
        /** @var AProtocol|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $conn->setTimeout(200);
        $protocol
            ->run('CALL apoc.util.sleep(150000)', [], ['mode' => 'r', 'tx_timeout' => 120000])
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     */
    public function testSecondsTimeout(array $factory): void
    {
        $conn = $this->getConnection($factory);
        $conn->setTimeout(1);
        /** @var AProtocol|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $this->expectException(ConnectionTimeoutException::class);
        $protocol
            ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
            ->getResponse();
    }

    /**
     * @dataProvider provideConnections
     */
    public function testTimeoutRecoverAndReset(array $factory): void
    {
        $conn = $this->getConnection($factory);
        /** @var AProtocol|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            iterator_to_array(
                $protocol
                    ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
                    ->pull()
                    ->getResponses(),
                false);
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException) {
            $newTime = microtime(true);
            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }

        $conn->setTimeout(15.0);
        $response = $protocol
            ->reset()
            ->getResponse();

        $this->assertEquals(Response::SIGNATURE_FAILURE, $response->getSignature());
        /** @var AProtocol|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        $conn->setTimeout(1.5);
        $time = microtime(true);
        try {
            $protocol
                ->run('FOREACH ( i IN range(1,10000) | MERGE (d:Day {day: i}) )')
                ->getResponse();
            $this->fail('No timeout error triggered');
        } catch (ConnectionTimeoutException) {
            $newTime = microtime(true);
            $this->assertGreaterThanOrEqual(1.0, $newTime - $time);
        }
    }

    private function getConnection(array $factory): IConnection
    {
        return call_user_func($factory);
    }
}
