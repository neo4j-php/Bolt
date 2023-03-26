<?php

namespace Bolt\tests\packstream\v1;

use Bolt\Bolt;
use Bolt\protocol\{AProtocol, Response, V4_4, V5, V5_1};
use Bolt\tests\ATest;
use Bolt\tests\CreatesSockets;

/**
 * Class UnpackerTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\packstream\v1
 */
class UnpackerTest extends ATest
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

        $conn->setTimeout(60 * 10);
        return $protocol;
    }

    /**
     * @depends testInit
     */
    public function testNull(AProtocol|V4_4|V5|v5_1 $protocol): void
    {
        $gen = $protocol
            ->run('RETURN null', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->getSignature() == Response::SIGNATURE_RECORD)
                $this->assertNull($response->getContent()[0]);
        }
    }

    /**
     * @depends testInit
     */
    public function testBoolean(AProtocol|V4_4|V5|v5_1 $protocol): void
    {
        $gen = $protocol
            ->run('RETURN true, false', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->getSignature() == Response::SIGNATURE_RECORD) {
                $this->assertTrue($response->getContent()[0]);
                $this->assertFalse($response->getContent()[1]);
            }
        }
    }

    /**
     * @depends testInit
     */
    public function testInteger(AProtocol|V4_4|V5|v5_1 $protocol): void
    {
        $gen = $protocol
            ->run('RETURN -16, 0, 127, -17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->getSignature() == Response::SIGNATURE_RECORD) {
                foreach ([-16, 0, 127, -17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808] as $i => $value) {
                    $this->assertEquals($value, $response->getContent()[$i]);
                }
            }
        }
    }

    /**
     * @depends testInit
     */
    public function testFloat(AProtocol|V4_4|V5|v5_1 $protocol): void
    {
        for ($i = 0; $i < 10; $i++) {
            $num = mt_rand(-mt_getrandmax(), mt_getrandmax()) / mt_getrandmax();

            $gen = $protocol
                ->run('RETURN ' . $num, [], ['mode' => 'r'])
                ->pull()
                ->getResponses();

            /** @var Response $response */
            foreach ($gen as $response) {
                if ($response->getSignature() == Response::SIGNATURE_RECORD) {
                    $this->assertEqualsWithDelta($num, $response->getContent()[0], 0.000001);
                }
            }
        }
    }

    /**
     * @depends      testInit
     * @dataProvider stringProvider
     */
    public function testString(string $str, AProtocol|V4_4|V5|v5_1 $protocol): void
    {
        $gen = $protocol
            ->run('RETURN "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $str) . '" AS a', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->getSignature() == Response::SIGNATURE_RECORD) {
                $this->assertEquals($str, $response->getContent()[0]);
            }
        }
    }

    public function stringProvider(): \Generator
    {
        foreach ([0, 10, 200, 60000, 200000] as $length)
            yield 'string length: ' . $length => [$this->randomString($length)];
    }

    private function randomString(int $length): string
    {
        $str = '';
        while (strlen($str) < $length)
            $str .= chr(mt_rand(32, 126));
        return $str;
    }

    /**
     * @depends      testInit
     * @dataProvider listProvider
     */
    public function testList(int $size, AProtocol|V4_4|V5|v5_1 $protocol): void
    {
        $gen = $protocol
            ->run('RETURN range(0, ' . $size . ') AS a', [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->getSignature() == Response::SIGNATURE_RECORD) {
                $this->assertEquals(range(0, $size), $response->getContent()[0]);
            }
        }
    }

    public function listProvider(): \Generator
    {
        foreach ([0, 10, 200, 60000, 200000] as $size)
            yield 'list size: ' . $size => [$size];
    }

    /**
     * @depends      testInit
     * @dataProvider dictionaryProvider
     */
    public function testDictionary(string $query, int $size, AProtocol|V4_4|V5|v5_1 $protocol): void
    {
        $gen = $protocol
            ->run($query, [], ['mode' => 'r'])
            ->pull()
            ->getResponses();

        /** @var Response $response */
        foreach ($gen as $response) {
            if ($response->getSignature() == Response::SIGNATURE_RECORD) {
                $this->assertCount($size, $response->getContent()[0]);
            } elseif ($response->getSignature() == Response::SIGNATURE_FAILURE) {
                $this->markTestIncomplete(print_r($response->getContent(), true));
            }
        }
    }

    public function dictionaryProvider(): \Generator
    {
        foreach ([0, 10, 200, 20000, 70000] as $size) {
            yield 'dictionary size: ' . $size => ['RETURN apoc.map.fromLists(toStringList(range(1, ' . $size . ')), range(1, ' . $size . '))', $size];
        }
    }

}
