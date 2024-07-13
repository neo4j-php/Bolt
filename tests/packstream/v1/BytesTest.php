<?php

namespace Bolt\tests\packstream\v1;

use Bolt\Bolt;
use Bolt\packstream\Bytes;
use Bolt\protocol\AProtocol;
use Bolt\tests\TestLayer;

/**
 * Class BytesTest
 * @package Bolt\tests\packstream\v1
 */
class BytesTest extends TestLayer
{
    public function testInit(): AProtocol
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = new Bolt($conn);
        $this->assertInstanceOf(Bolt::class, $bolt);

        $protocol = $bolt->setProtocolVersions($this->getCompatibleBoltVersion())->build();
        $this->assertInstanceOf(AProtocol::class, $protocol);

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        return $protocol;
    }

    /**
     * @depends      testInit
     * @dataProvider providerBytes
     */
    public function testBytes(Bytes $arr, AProtocol $protocol)
    {
        $res = iterator_to_array(
            $protocol
                ->run('RETURN $arr', ['arr' => $arr])
                ->pull()
                ->getResponses(),
            false
        );
        $this->assertEquals($arr, $res[1]->content[0]);
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
