<?php

namespace Bolt\tests\structures\v5;

use Bolt\Bolt;
use Bolt\protocol\{
    AProtocol,
    Response,
    V4_3,
    V4_4,
    V5
};
use Bolt\tests\structures\v1\DateTimeTrait;
use Bolt\tests\structures\v1\DateTimeZoneIdTrait;
use Bolt\protocol\v5\structures\{
    DateTime,
    DateTimeZoneId,
    Node,
    Relationship,
    UnboundRelationship
};
use Bolt\protocol\v1\structures\Path;

/**
 * Class StructuresTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\v5\structures\DateTime
 * @covers \Bolt\protocol\v5\structures\DateTimeZoneId
 *
 * @covers \Bolt\packstream\v1\Packer
 * @covers \Bolt\packstream\v1\Unpacker
 *
 * @package Bolt\tests\protocol\v5
 */
class StructuresTest extends \Bolt\tests\structures\AStructures
{
    public function testInit(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V4_3|V4_4|V5 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        if (version_compare($protocol->getVersion(), '5', '<')) {
            $this->markTestSkipped('Tests available only for version 5 and higher.');
        }

        $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

        return $protocol;
    }

    private string $expectedDateTimeClass = DateTime::class;
    use DateTimeTrait;

    private string $expectedDateTimeZoneIdClass = DateTimeZoneId::class;
    use DateTimeZoneIdTrait;

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testNode(AProtocol $protocol)
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE (a:Test { param1: 123 }) RETURN a, ID(a), elementId(a)')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Node::class, $res[1]->getContent()[0]);

        $this->assertEquals($res[1]->getContent()[1], $res[1]->getContent()[0]->id());
        $this->assertEquals($res[1]->getContent()[2], $res[1]->getContent()[0]->element_id());
        $this->assertEquals(['Test'], $res[1]->getContent()[0]->labels());
        $this->assertEquals(['param1' => 123], $res[1]->getContent()[0]->properties());

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testPath(AProtocol $protocol)
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE p=(:Test)-[r:HAS { param1: 123 }]->(:Test) RETURN p, ID(r), elementId(r)')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Path::class, $res[1]->getContent()[0]);

        foreach ($res[1]->getContent()[0]->rels() as $rel) {
            $this->assertInstanceOf(UnboundRelationship::class, $rel);

            $this->assertEquals($res[1]->getContent()[1], $rel->id());
            $this->assertEquals($res[1]->getContent()[2], $rel->element_id());
            $this->assertEquals('HAS', $rel->type());
            $this->assertEquals(['param1' => 123], $rel->properties());
        }

        //pack not supported

        $protocol->rollback()->getResponse();
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testRelationship(AProtocol $protocol)
    {
        $protocol->begin()->getResponse();

        //unpack
        $res = iterator_to_array(
            $protocol
                ->run('CREATE (a:Test)-[rel:HAS { param1: 123 }]->(b:Test) RETURN rel, ID(rel), elementId(rel), ID(a), ID(b), elementId(a), elementId(b)')
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertInstanceOf(Relationship::class, $res[1]->getContent()[0]);

        $this->assertEquals($res[1]->getContent()[1], $res[1]->getContent()[0]->id());
        $this->assertEquals($res[1]->getContent()[2], $res[1]->getContent()[0]->element_id());
        $this->assertEquals('HAS', $res[1]->getContent()[0]->type());
        $this->assertEquals(['param1' => 123], $res[1]->getContent()[0]->properties());
        $this->assertEquals($res[1]->getContent()[3], $res[1]->getContent()[0]->startNodeId());
        $this->assertEquals($res[1]->getContent()[4], $res[1]->getContent()[0]->endNodeId());
        $this->assertEquals($res[1]->getContent()[5], $res[1]->getContent()[0]->start_node_element_id());
        $this->assertEquals($res[1]->getContent()[6], $res[1]->getContent()[0]->end_node_element_id());

        //pack not supported

        $protocol->rollback()->getResponse();
    }
}
