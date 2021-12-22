<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Exception;

/**
 * Class BoltTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\Bolt
 * @covers \Bolt\connection\AConnection
 * @covers \Bolt\connection\Socket
 * @covers \Bolt\connection\StreamSocket
 * @covers \Bolt\helpers\Auth
 * @covers \Bolt\PackStream\v1\Packer
 * @covers \Bolt\PackStream\v1\Unpacker
 * @covers \Bolt\protocol\V1
 * @covers \Bolt\protocol\V2
 * @covers \Bolt\protocol\V3
 * @covers \Bolt\protocol\V4
 * @covers \Bolt\protocol\V4_1
 * @covers \Bolt\protocol\V4_2
 * @covers \Bolt\protocol\V4_3
 * @covers \Bolt\protocol\V4_4
 *
 * @package Bolt\tests
 * @requires PHP >= 7.1
 * @requires extension sockets
 * @requires extension mbstring
 */
class BoltTest extends ATest
{

    private static $version;

    public function testSockets()
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        Bolt::$debug = true;

        try {
            $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687, 3);
            $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertIsArray($protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS'])));

            if (method_exists($protocol, 'goodbye'))
                $protocol->goodbye();
            else
                $conn->disconnect();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @return AProtocol
     */
    public function testHello(): AProtocol
    {
        Bolt::$debug = true;

        try {
            $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
            $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertNotEmpty($protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS'])));

            self::$version = $bolt->getProtocolVersion();
            return $protocol;
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $bolt
     */
    public function testPull(AProtocol $bolt)
    {
        try {
            $res = $bolt->run('RETURN 1 AS num, 2 AS cnt');
            $this->assertIsArray($res);
            $this->assertArrayHasKey('fields', $res);

            $res = $bolt->pullAll();
            $this->assertEquals(1, $res[0][0] ?? 0);
            $this->assertEquals(2, $res[0][1] ?? 0);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $bolt
     */
    public function testDiscard(AProtocol $bolt)
    {
        try {
            $this->assertNotFalse($bolt->run('MATCH (a:Test) RETURN *'));
            $this->assertIsArray($bolt->discardAll());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $bolt
     */
    public function testNode(AProtocol $bolt)
    {
        try {
            $this->assertNotFalse($bolt->run('CREATE (a:Test) RETURN a, ID(a)'));

            $created = $bolt->pullAll();
            $this->assertIsArray($created);
            $this->assertInstanceOf(\Bolt\structures\Node::class, $created[0][0]);

            $this->assertNotFalse($bolt->run('MATCH (a:Test) WHERE ID(a) = ' . $this->formatParameter($bolt, 'a') . ' DELETE a', [
                'a' => $created[0][1]
            ]));
            $this->assertEquals(1, $bolt->pullAll()[0]['stats']['nodes-deleted'] ?? 0);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $bolt
     */
    public function testTransaction(AProtocol $bolt)
    {
        if (self::$version < 3) {
            $this->markTestSkipped('Old Neo4j version does not support transactions');
        }

        try {
            $this->assertIsArray($bolt->begin());
            $this->assertIsArray($bolt->run('CREATE (a:Test) RETURN a, ID(a)'));
            $created = $bolt->pullAll();
            $this->assertIsArray($created);
            $this->assertIsArray($bolt->rollback());

            $this->assertIsArray($bolt->run('MATCH (a:Test) WHERE ID(a) = ' . $this->formatParameter($bolt, 'a') . ' RETURN COUNT(a)', [
                'a' => $created[0][1]
            ]));
            $res = $bolt->pullAll();
            $this->assertIsArray($res);
            $this->assertEquals(0, $res[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @var bool
     */
    private static $parameterType;

    /**
     * Because from Neo4j >= 4.0 is different placeholder for parameters
     * @param AProtocol $bolt
     * @param string $name
     * @return string
     * @throws Exception
     */
    private function formatParameter(AProtocol $bolt, string $name): string
    {
        if (self::$parameterType == null) {
            $this->assertNotFalse($bolt->run('call dbms.components() yield versions unwind versions as version return version'));
            $neo4jVersion = $bolt->pullAll()[0][0] ?? '';
            $this->assertNotEmpty($neo4jVersion);
            self::$parameterType = version_compare($neo4jVersion, '4') == -1;
        }

        return self::$parameterType ? ('{' . $name . '}') : ('$' . $name);
    }

    /**
     * @depends testHello
     * @param AProtocol $bolt
     */
    public function testRoute(AProtocol $bolt): void
    {
        if (self::$version >= 4.3) {
            self::assertIsArray($bolt->route([
                'address' => ($GLOBALS['NEO_HOST'] ?? '127.0.0.1') . ':' . ($GLOBALS['NEO_PORT'] ?? 7687)
            ], [], []));
        } else {
            $this->markTestSkipped('Old Neo4j version does not support route message');
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $bolt
     */
    public function testReset(AProtocol $bolt): void
    {
        try {
            $this->assertIsArray($bolt->reset());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }
}
