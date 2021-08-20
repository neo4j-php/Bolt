<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Exception;

/**
 * Class BoltTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 *
 * @covers \Bolt\Bolt
 * @covers \Bolt\connection\AConnection
 * @covers \Bolt\connection\Socket
 * @covers \Bolt\connection\StreamSocket
 * @covers \Bolt\PackStream\v1\Packer
 * @covers \Bolt\PackStream\v1\Unpacker
 *
 * @package Bolt\tests
 * @requires PHP >= 7.1
 * @requires extension sockets
 * @requires extension mbstring
 */
class BoltTest extends ATest
{

    /**
     * @return Bolt|null
     */
    public function testHello(): ?Bolt
    {
        Bolt::$debug = true;

        try {
            if (extension_loaded('sockets')) {
                $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
                $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);
                $bolt = new Bolt($conn);
                $this->assertInstanceOf(Bolt::class, $bolt);
                $this->assertTrue($bolt->hello('Test/1.0', $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
            }
            unset($bolt);

            $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
            $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);
            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);
            $this->assertTrue($bolt->hello('Test/1.0', $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

            return $bolt;
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }

        return null;
    }

    /**
     * @depends testHello
     * @param Bolt $bolt
     */
    public function testPull(Bolt $bolt)
    {
        try {
            $res = $bolt->run('RETURN 1 AS num, 2 AS cnt');
            $this->assertIsArray($res);
            $this->assertArrayHasKey('fields', $res);

            $res = $bolt->pull();
            $this->assertEquals(1, $res[0][0] ?? 0);
            $this->assertEquals(2, $res[0][1] ?? 0);
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param Bolt $bolt
     */
    public function testDiscard(Bolt $bolt)
    {
        try {
            $this->assertNotFalse($bolt->run('MATCH (a:Test) RETURN *'));
            $this->assertTrue($bolt->discard());
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param Bolt $bolt
     */
    public function testNode(Bolt $bolt)
    {
        try {
            $this->assertNotFalse($bolt->run('CREATE (a:Test) RETURN a, ID(a)'));

            $created = $bolt->pull();
            $this->assertIsArray($created);
            $this->assertInstanceOf(\Bolt\structures\Node::class, $created[0][0]);

            $this->assertNotFalse($bolt->run('MATCH (a:Test) WHERE ID(a) = ' . $this->formatParameter($bolt, 'a') . ' DELETE a', [
                'a' => $created[0][1]
            ]));
            $this->assertEquals(1, $bolt->pull()[0]['stats']['nodes-deleted'] ?? 0);
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param Bolt $bolt
     */
    public function testTransaction(Bolt $bolt)
    {
        if ($bolt->getProtocolVersion() < 3) {
            $this->markTestSkipped('Old Neo4j version does not support transactions');
            return;
        }

        try {
            $this->assertTrue($bolt->begin());
            $this->assertNotFalse($bolt->run('CREATE (a:Test) RETURN a, ID(a)'));
            $created = $bolt->pull();
            $this->assertIsArray($created);
            $this->assertTrue($bolt->rollback());

            $this->assertNotFalse($bolt->run('MATCH (a:Test) WHERE ID(a) = ' . $this->formatParameter($bolt, 'a') . ' RETURN COUNT(a)', [
                'a' => $created[0][1]
            ]));
            $res = $bolt->pull();
            $this->assertIsArray($res);
            $this->assertEquals(0, $res[0][0]);
        } catch (Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * @var bool
     */
    private static $parameterType;

    /**
     * Because from Neo4j >= 4.0 is different placeholder for parameters
     * @param Bolt $bolt
     * @param string $name
     * @return string
     * @throws Exception
     */
    private function formatParameter(Bolt $bolt, string $name): string
    {
        if (self::$parameterType == null) {
            $this->assertNotFalse($bolt->run('call dbms.components() yield versions unwind versions as version return version'));
            $neo4jVersion = $bolt->pull()[0][0] ?? '';
            $this->assertNotEmpty($neo4jVersion);
            self::$parameterType = version_compare($neo4jVersion, '4') == -1;
        }

        return self::$parameterType ? ('{' . $name . '}') : ('$' . $name);
    }



    /**
     * @depends testHello
     * @param Bolt $bolt
     */
    public function testRoute(Bolt $bolt): void
    {
        $version = $bolt->getProtocolVersion();
        if ($version >= 4.3) {
            $route = $bolt->route();
            self::assertNotEmpty($route);
        } else {
            self::assertNull($bolt->route());
        }
    }
}
