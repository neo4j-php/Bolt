<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Exception;
use PHPUnit\Framework\TestCase;
use Bolt\protocol\{Response, V4_3, V4_4};

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
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_3
 * @covers \Bolt\protocol\V4_4
 * @covers \Bolt\protocol\Response
 * @covers \Bolt\protocol\ServerState
 *
 * @package Bolt\tests
 * @requires PHP >= 7.1
 * @requires extension sockets
 * @requires extension mbstring
 */
class BoltTest extends TestCase
{

    public function testSockets()
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        try {
            $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687, 3);
            $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            /** @var V4_3|V4_4 $protocol */
            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

            $protocol->goodbye();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    public function testAura()
    {
        try {
            $conn = new \Bolt\connection\StreamSocket('neo4j+s://demo.neo4jlabs.com');
            $conn->setSslContextOptions([
                'verify_peer' => true
            ]);
            $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            /** @var V4_3|V4_4 $protocol */
            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic('movies', 'movies'))->getSignature());

            $protocol->goodbye();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @return AProtocol
     */
    public function testHello(): AProtocol
    {
        try {
            $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
            $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            /** @var V4_3|V4_4 $protocol */
            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

            return $protocol;
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     */
    public function testPull(AProtocol $protocol)
    {
        try {
            $protocol
                ->run('RETURN 1 AS num, 2 AS cnt', [], ['mode' => 'r'])
                ->pull();

            $this->assertArrayHasKey('fields', $protocol->getResponse()->getContent());

            $res = $protocol->getResponse()->getContent();
            $this->assertEquals(1, $res[0] ?? 0);
            $this->assertEquals(2, $res[1] ?? 0);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     * @doesNotPerformAssertions
     */
    public function testDiscard(AProtocol $protocol)
    {
        try {
            $gen = $protocol
                ->run('MATCH (a:Test) RETURN *', [], ['mode' => 'r'])
                ->discard()
                ->getResponses();
            iterator_to_array($gen, false);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
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

        try {
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
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     * @doesNotPerformAssertions
     */
    public function testRoute(AProtocol $protocol): void
    {
        if (version_compare($protocol->getVersion(), 4.3, '>=')) {
            try {
                $gen = $protocol
                    ->route([
                        'address' => ($GLOBALS['NEO_HOST'] ?? '127.0.0.1') . ':' . ($GLOBALS['NEO_PORT'] ?? 7687)
                    ])
                    ->getResponses();
                iterator_to_array($gen, false);
            } catch (Exception $e) {
                $this->markTestIncomplete($e->getMessage());
            }
        } else {
            $this->markTestSkipped('Old Neo4j version does not support route message');
        }
    }

    /**
     * @depends testHello
     * @param AProtocol|V4_3|V4_4 $protocol
     * @doesNotPerformAssertions
     */
    public function testReset(AProtocol $protocol): void
    {
        try {
            $gen = $protocol
                ->reset()
                ->getResponses();
            iterator_to_array($gen, false);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
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
            try {
                $gen = $protocol
                    ->run('MATCH (a:Test) WHERE ID(a) = $id SET a += $data RETURN a', [
                        'id' => $id,
                        'data' => (object)$data
                    ])
                    ->pull()
                    ->getResponses();
                $result = iterator_to_array($gen, false);
                $this->assertInstanceOf(\Bolt\structures\Node::class, $result[1]->getContent()[0]);
                $this->assertCount(count($data), $result[1]->getContent()[0]->properties());
            } catch (Exception $e) {
                $this->markTestIncomplete();
            }
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
