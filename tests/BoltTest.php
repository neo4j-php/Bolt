<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Exception;
use PHPUnit\Framework\TestCase;
use Bolt\protocol\{
    AProtocol,
    Response,
    V4_3,
    V4_4,
    V5
};

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
 * @covers \Bolt\packstream\v1\Packer
 * @covers \Bolt\packstream\v1\Unpacker
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_3
 * @covers \Bolt\protocol\V4_4
 * @covers \Bolt\protocol\V5
 * @covers \Bolt\protocol\Response
 * @covers \Bolt\protocol\ServerState
 *
 * @package Bolt\tests
 */
class BoltTest extends TestCase
{

    public function testSockets()
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687, 3);
        $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

        $protocol->goodbye();
    }

    public function testAura()
    {
        $conn = new \Bolt\connection\StreamSocket('neo4j+s://demo.neo4jlabs.com');
        $conn->setSslContextOptions([
            'verify_peer' => true
        ]);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic('movies', 'movies'))->getSignature());

        $protocol->goodbye();
    }

    /**
     * @return AProtocol
     */
    public function testHello(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

        return $protocol;
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     */
    public function testPull(AProtocol $protocol)
    {
        $protocol
            ->run('RETURN 1 AS num, 2 AS cnt', [], ['mode' => 'r'])
            ->pull();

        $this->assertArrayHasKey('fields', $protocol->getResponse()->getContent());

        $res = $protocol->getResponse()->getContent();
        $this->assertEquals(1, $res[0] ?? 0);
        $this->assertEquals(2, $res[1] ?? 0);
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     */
    public function testDiscard(AProtocol $protocol)
    {
        $gen = $protocol
            ->run('MATCH (a:Test) RETURN *', [], ['mode' => 'r'])
            ->discard()
            ->getResponses();

        foreach ($gen as $response) {
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     * @throws Exception
     */
    public function testTransaction(AProtocol $protocol)
    {
        if (version_compare($protocol->getVersion(), 3, '<')) {
            $this->markTestSkipped('Old Neo4j version does not support transactions');
        }

        $res = iterator_to_array(
            $protocol
                ->begin()
                ->run('CREATE (a:Test) RETURN a, ID(a)')
                ->pull()
                ->rollback()
                ->getResponses(),
            false
        );

        $id = $res[2]->getContent()[1];
        $this->assertIsInt($id);

        $res = iterator_to_array(
            $protocol
                ->run('MATCH (a:Test) WHERE ID(a) = '
                    . (version_compare($protocol->getVersion(), 4, '<') ? '{a}' : '$a')
                    . ' RETURN COUNT(a)', [
                    'a' => $id
                ])
                ->pull()
                ->getResponses(),
            false
        );

        $this->assertEquals(0, $res[1]->getContent()[0]);
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     */
    public function testRoute(AProtocol $protocol): void
    {
        if (version_compare($protocol->getVersion(), 4.3, '>=')) {
            $response = $protocol
                ->route([
                    'address' => ($GLOBALS['NEO_HOST'] ?? '127.0.0.1') . ':' . ($GLOBALS['NEO_PORT'] ?? 7687)
                ])
                ->getResponse();
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());
        } else {
            $this->markTestSkipped('Old Neo4j version does not support route message');
        }
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     */
    public function testReset(AProtocol $protocol): void
    {
        $response = $protocol
            ->reset()
            ->getResponse();
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());
    }

    /**
     * @large
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     * @throws Exception
     */
    public function testChunking(AProtocol $protocol)
    {
        $gen = $protocol
            ->begin()
            ->run('CREATE (a:Test) RETURN ID(a)')
            ->pull()
            ->getResponses();
        $id = iterator_to_array($gen, false)[2]->getContent()[0];

        $data = [];
        while (strlen(serialize($data)) < 65535 * 2) {
            $data[base64_encode(random_bytes(32))] = base64_encode(random_bytes(128));
            $gen = $protocol
                ->run('MATCH (a:Test) WHERE ID(a) = $id SET a += $data RETURN a', [
                    'id' => $id,
                    'data' => (object)$data
                ])
                ->pull()
                ->getResponses();
            $result = iterator_to_array($gen, false);
            $this->assertInstanceOf(\Bolt\protocol\v1\structures\Node::class, $result[1]->getContent()[0]);
            $this->assertCount(count($data), $result[1]->getContent()[0]->properties());
        }

        $protocol->rollback();
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     */
    public function testServerStateMismatchCallback(AProtocol $protocol)
    {
        $protocol->serverState->set(\Bolt\protocol\ServerState::FAILED);
        $protocol->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            throw new Exception('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };

        $this->expectException(Exception::class);
        $gen = $protocol->run('RETURN 1 as num')->getResponses();
        iterator_to_array($gen, false);
    }
}
