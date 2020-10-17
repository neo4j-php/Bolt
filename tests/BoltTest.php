<?php

namespace Bolt\tests;

use PHPUnit\Framework\TestCase;
use Bolt\Bolt;

/**
 * Class BoltTest
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @covers \Bolt\Bolt
 * @package Bolt\tests
 * @requires PHP >= 7.1
 * @requires extension sockets
 * @requires extension mbstring
 */
class BoltTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        Bolt::$errorHandler = function ($msg, $code) {
            echo $msg . ' (' . $code . ')' . PHP_EOL;
        };

        //Todo pridat ked bude debugHandler aby sa dal naformatovat output
        //Bolt::$debug = true;
    }

    /**
     * @return Bolt
     * @throws \Exception
     */
    public function test__construct()
    {
        $bolt = new Bolt($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(Bolt::class, $bolt);
        return $bolt;
    }

    /**
     * @depends test__construct
     * @param Bolt $bolt
     * @return Bolt
     * @throws \Exception
     */
    public function testInit(Bolt $bolt)
    {
        $this->assertTrue($bolt->init('Test/1.0', $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        return $bolt;
    }

    /**
     * @depends testInit
     * @param Bolt $bolt
     * @return Bolt
     */
    public function testRun(Bolt $bolt)
    {
        $res = $bolt->run('RETURN 1 AS num, 2 AS cnt');
        $this->assertIsArray($res);
        $this->assertArrayHasKey('fields', $res);
        return $bolt;
    }

    /**
     * @depends testRun
     * @param Bolt $bolt
     * @return Bolt
     */
    public function testPull(Bolt $bolt)
    {
        $res = $bolt->pull();
        $this->assertEquals(1, $res[0][0] ?? 0);
        $this->assertEquals(2, $res[0][1] ?? 0);
        return $bolt;
    }

    /**
     * @depends testInit
     * @param Bolt $bolt
     */
    public function testDiscard(Bolt $bolt)
    {
        //test discard
        $this->assertNotFalse($bolt->run('MATCH (a:Test) RETURN *'));
        $this->assertTrue($bolt->discard());
    }

    /**
     * @depends testInit
     * @depends testPull
     * @param Bolt $bolt
     * @return int
     */
    public function testNodeCreate(Bolt $bolt)
    {
        $this->assertNotFalse($bolt->run('CREATE (a:Test) RETURN a, ID(a)'));

        $created = $bolt->pull();
        $this->assertIsArray($created);
        $this->assertInstanceOf(\Bolt\structures\Node::class, $created[0][0]);
        return $created[0][1];
    }

    /**
     * @depends testInit
     * @depends testNodeCreate
     * @param Bolt $bolt
     * @param int $id
     */
    public function testNodeDelete(Bolt $bolt, int $id)
    {
        //test delete created node
        $this->assertNotFalse($bolt->run('MATCH (a:Test) WHERE ID(a) = ' . ($this->getParameterType($bolt) ? '{a}' : '$a') . ' DELETE a', [
            'a' => $id
        ]));
        $this->assertEquals(1, $bolt->pull()[0]['stats']['nodes-deleted'] ?? 0);
    }

    /**
     * @depends testInit
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

        $this->assertNotFalse($bolt->run('MATCH (a:Test) WHERE ID(a) = ' . ($this->getParameterType($bolt) ? '{a}' : '$a') . ' RETURN COUNT(a)', [
            'a' => $created[0][1]
        ]));
        $res = $bolt->pull();
        $this->assertIsArray($res);
        $this->assertEquals(0, $res[0][0]);
    }

    /**
     * @var bool
     */
    private static $parameterType;

    /**
     * Because from Neo4j >= 4.0 is different placeholder for parameters
     * @param Bolt $bolt
     * @return bool
     */
    private function getParameterType(Bolt $bolt): bool
    {
        if (self::$parameterType == null) {
            $this->assertNotFalse($bolt->run('call dbms.components() yield versions unwind versions as version return version'));
            $neo4jVersion = $bolt->pull()[0][0] ?? '';
            $this->assertNotEmpty($neo4jVersion);
            self::$parameterType = version_compare($neo4jVersion, '4') == -1;
        }

        return self::$parameterType;
    }

}
