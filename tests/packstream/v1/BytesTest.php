<?php

namespace Bolt\tests\packstream\v1;

use Bolt\Bolt;
use Bolt\packstream\Bytes;
use Bolt\protocol\{AProtocol, V4_4, V5, V5_1};
use Bolt\tests\ATest;
use Bolt\tests\CreatesSockets;

/**
 * Class BytesTest
 * @package Bolt\tests\packstream\v1
 */
class BytesTest extends ATest
{
    use CreatesSockets;

    public function testInit(): AProtocol|V4_4|V5|v5_1
    {
        $conn = $this->createStreamSocket();

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        /** @var AProtocol|V4_4|V5|v5_1 $protocol */
        $protocol = $bolt->setProtocolVersions(5.1, 5, 4.4)->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        return $protocol;
    }

    /**
     * @depends      testInit
     * @dataProvider providerBytes
     */
    public function testBytes(Bytes $arr, AProtocol|V4_4|V5|v5_1 $protocol)
    {
        $res = iterator_to_array(
            $protocol
                ->run('RETURN $arr', ['arr' => $arr])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($arr, $res[1]->getContent()[0]);
    }

    public function providerBytes(): \Generator
    {
        foreach ([1, 200, 60000, 70000] as $size) {
            $arr = new Bytes();
            while (count($arr) < $size) {
                $arr[] = pack('H', mt_rand(0, 255));
            }
            yield 'bytes: ' . count($arr) => [$arr];
        }
    }
}
