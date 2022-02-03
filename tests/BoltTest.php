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

            return $protocol;
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $protocol
     */
    public function testPull(AProtocol $protocol)
    {
        try {
            $res = $protocol->run('RETURN 1 AS num, 2 AS cnt');
            $this->assertIsArray($res);
            $this->assertArrayHasKey('fields', $res);

            $res = $protocol->pullAll();
            $this->assertEquals(1, $res[0][0] ?? 0);
            $this->assertEquals(2, $res[0][1] ?? 0);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $protocol
     */
    public function testDiscard(AProtocol $protocol)
    {
        try {
            $this->assertNotFalse($protocol->run('MATCH (a:Test) RETURN *'));
            $this->assertIsArray($protocol->discardAll());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $protocol
     * @throws Exception
     */
    public function testTransaction(AProtocol $protocol)
    {
        if (version_compare($protocol->getVersion(), 3, '<')) {
            $this->markTestSkipped('Old Neo4j version does not support transactions');
        }

        try {
            $this->assertIsArray($protocol->begin());
            $this->assertIsArray($protocol->run('CREATE (a:Test) RETURN a, ID(a)'));
            $created = $protocol->pullAll();
            $this->assertIsArray($created);
            $this->assertIsArray($protocol->rollback());

            $this->assertIsArray($protocol->run('MATCH (a:Test) WHERE ID(a) = '
                . (version_compare($protocol->getVersion(), 4, '<') ? '{a}' : '$a')
                . ' RETURN COUNT(a)', [
                'a' => $created[0][1]
            ]));
            $res = $protocol->pullAll();
            $this->assertIsArray($res);
            $this->assertEquals(0, $res[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $protocol
     * @throws Exception
     */
    public function testRoute(AProtocol $protocol): void
    {
        if (version_compare($protocol->getVersion(), 4.3, '>=')) {
            self::assertIsArray($protocol->route([
                'address' => ($GLOBALS['NEO_HOST'] ?? '127.0.0.1') . ':' . ($GLOBALS['NEO_PORT'] ?? 7687)
            ], [], []));
        } else {
            $this->markTestSkipped('Old Neo4j version does not support route message');
        }
    }

    /**
     * @depends testHello
     * @param AProtocol $protocol
     */
    public function testReset(AProtocol $protocol): void
    {
        try {
            $this->assertIsArray($protocol->reset());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @large
     * @depends testHello
     * @param AProtocol $protocol
     * @throws Exception
     */
    public function testChunking(AProtocol $protocol)
    {
        Bolt::$debug = false;

        $protocol->begin();
        $protocol->run('CREATE (a:Test) RETURN ID(a)');
        $result = $protocol->pull();

        $data = [];
        while (strlen(serialize($data)) < 65535 * 2) {
            $data[base64_encode(random_bytes(32))] = base64_encode(random_bytes(128));
            try {
                $run = $protocol->run('MATCH (a:Test) WHERE ID(a) = $id SET a += $data RETURN a', [
                    'id' => $result[0][0],
                    'data' => (object)$data
                ]);
                $this->assertIsArray($run);

                $pull = $protocol->pull();
                $this->assertIsArray($pull);
                $this->assertInstanceOf(\Bolt\structures\Node::class, $pull[0][0]);
                $this->assertCount(count($data), $pull[0][0]->properties());
            } catch (Exception $e) {
                $this->markTestIncomplete();
                break;
            }
        }

        $protocol->rollback();
    }
}
