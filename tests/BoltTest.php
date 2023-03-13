<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Exception;
use Bolt\protocol\{AProtocol, Response, V1, V2, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1};

/**
 * Class BoltTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class BoltTest extends ATest
{
    public function testSockets(): void
    {
        if (!extension_loaded('sockets'))
            $this->markTestSkipped('Sockets extension not available');

        $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687, 3);
        $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        $protocol->goodbye();
    }

    public function testAura(): void
    {
        $conn = new \Bolt\connection\StreamSocket('neo4j+s://demo.neo4jlabs.com');
        $conn->setSslContextOptions([
            'verify_peer' => true
        ]);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|v5_1 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, 'movies', 'movies');

        $protocol->goodbye();
    }

    public function testHello(): AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        return $protocol;
    }

    /**
     * @depends testHello
     */
    public function testPull(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol): void
    {
        $protocol
            ->run('RETURN 1 AS num, 2 AS cnt', [], ['mode' => 'r'])
            ->pull();

        $this->assertArrayHasKey('fields', $protocol->getResponse()->getContent());

        $res = $protocol->getResponse()->getContent();
        $this->assertEquals(1, $res[0] ?? 0);
        $this->assertEquals(2, $res[1] ?? 0);
        $protocol->getResponse(); // last success message
    }

    /**
     * @depends testHello
     */
    public function testDiscard(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol): void
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
     * @throws Exception
     */
    public function testTransaction(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol): void
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
     */
    public function testRoute(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol): void
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
     */
    public function testReset(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol): void
    {
        $response = $protocol
            ->reset()
            ->getResponse();
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());
    }

    /**
     * @large
     * @depends testHello
     * @throws Exception
     */
    public function testChunking(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol): void
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
     */
    public function testServerStateMismatchCallback(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol): void
    {
        $protocol->serverState->set(\Bolt\protocol\ServerState::FAILED);
        $protocol->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            throw new Exception('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };

        $this->expectException(Exception::class);
        iterator_to_array($protocol->run('RETURN 1 as num')->getResponses(), false);
    }
}
