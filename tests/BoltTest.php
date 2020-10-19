<?php

namespace Bolt\tests;

use Bolt\Bolt;

/**
 * Class BoltTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 *
 * @covers \Bolt\Bolt
 * @covers \Bolt\Socket
 *
 * @package Bolt\tests
 * @requires PHP >= 7.1
 * @requires extension sockets
 * @requires extension mbstring
 */
class BoltTest extends \Bolt\tests\ATest
{

    /**
     * @return Bolt|null
     */
    public function testHello(): ?Bolt
    {
        try {
            $bolt = new Bolt($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
            $this->assertInstanceOf(Bolt::class, $bolt);
            $this->assertTrue($bolt->hello('Test/1.0', $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
            return $bolt;
        } catch (\Exception $e) {
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
        $res = $bolt->run('RETURN 1 AS num, 2 AS cnt');
        $this->assertIsArray($res);
        $this->assertArrayHasKey('fields', $res);

        $res = $bolt->pull();
        $this->assertEquals(1, $res[0][0] ?? 0);
        $this->assertEquals(2, $res[0][1] ?? 0);
    }

    /**
     * @depends testHello
     * @param Bolt $bolt
     */
    public function testDiscard(Bolt $bolt)
    {
        $this->assertNotFalse($bolt->run('MATCH (a:Test) RETURN *'));
        $this->assertTrue($bolt->discard());
    }

    /**
     * @depends testHello
     * @param Bolt $bolt
     */
    public function testNode(Bolt $bolt)
    {
        $this->assertNotFalse($bolt->run('CREATE (a:Test) RETURN a, ID(a)'));

        $created = $bolt->pull();
        $this->assertIsArray($created);
        $this->assertInstanceOf(\Bolt\structures\Node::class, $created[0][0]);

        $this->assertNotFalse($bolt->run('MATCH (a:Test) WHERE ID(a) = ' . $this->formatParameter($bolt, 'a') . ' DELETE a', [
            'a' => $created[0][1]
        ]));
        $this->assertEquals(1, $bolt->pull()[0]['stats']['nodes-deleted'] ?? 0);
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
    }

    public function testError()
    {
        $this->expectException(\Exception::class);
        Bolt::error('test');
    }

    public function testErrorHandler()
    {
        $tmp = '';
        Bolt::$errorHandler = function ($msg, $code) use (&$tmp) {
            $tmp = $msg;
        };
        Bolt::error('test');
        $this->assertEquals('test', $tmp);
        Bolt::$errorHandler = null;
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

}
