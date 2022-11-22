<?php

namespace Bolt\tests\structures\v4_3;

use Bolt\Bolt;
use Bolt\protocol\{
    AProtocol,
    Response,
    V4_3,
    V4_4
};
use Bolt\protocol\v5\structures\{
    DateTime,
    DateTimeZoneId
};
use Bolt\tests\structures\v1\{
    DateTimeTrait,
    DateTimeZoneIdTrait
};

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
 * @package Bolt\tests\protocol\v4_3
 */
class StructuresTest extends \Bolt\tests\structures\AStructures
{
    public function testInit(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $bolt->setProtocolVersions(4.4, 4.3);
        /** @var AProtocol|V4_4|V4_3 $protocol */
        $protocol = $bolt->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $extra = \Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $extra['patch_bolt'] = ['utc'];
        /** @var Response $helloResponse */
        $helloResponse = $protocol->hello($extra);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $helloResponse->getSignature());

        if (version_compare($protocol->getVersion(), '5', '>=') || version_compare($protocol->getVersion(), '4.3', '<')) {
            $this->markTestSkipped('You are not running Neo4j version with patch_bolt support.');
        }

        if (($helloResponse->getContent()['patch_bolt'] ?? null) !== ['utc']) {
            $this->markTestSkipped('Currently used Neo4j version does not support patch_bolt.');
        }

        return $protocol;
    }

    private string $expectedDateTimeClass = DateTime::class;
    use DateTimeTrait;

    private string $expectedDateTimeZoneIdClass = DateTimeZoneId::class;
    use DateTimeZoneIdTrait;
}
