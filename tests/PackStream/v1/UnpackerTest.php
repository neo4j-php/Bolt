<?php

namespace Bolt\tests\PackStream\v1;

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class UnpackerTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\PackStream\v1\Unpacker
 *
 * @package Bolt\tests\PackStream\v1
 * @requires PHP >= 7.1
 * @requires extension mbstring
 * @requires extension json
 */
class UnpackerTest extends TestCase
{
    public function testInit(): AProtocol
    {
        try {
            $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687, 15);
            $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertNotEmpty($protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS'])));

            //$conn->setTimeout(0);
            return $protocol;
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testNull(AProtocol $protocol)
    {
        $protocol->run('RETURN null');
        $res = $protocol->pull();
        $this->assertNull($res[0][0]);
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testBoolean(AProtocol $protocol)
    {
        $protocol->run('RETURN true, false');
        $res = $protocol->pull();
        $this->assertTrue($res[0][0]);
        $this->assertFalse($res[0][1]);
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testInteger(AProtocol $protocol)
    {
        $protocol->run('RETURN -16, 0, 127, -17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808');
        $res = $protocol->pull();

        foreach ([-16, 0, 127, -17, -128, 128, 32767, 32768, 2147483647, 2147483648, 9223372036854775807, -129, -32768, -32769, -2147483648, -2147483649, -9223372036854775808] as $i => $value) {
            $this->assertEquals($value, $res[0][$i]);
        }
    }

    /**
     * @depends testInit
     * @param AProtocol $protocol
     */
    public function testFloat(AProtocol $protocol)
    {
        for ($i = 0; $i < 10; $i++) {
            $num = mt_rand(-mt_getrandmax(), mt_getrandmax()) / mt_getrandmax();
            $protocol->run('RETURN ' . $num);
            $res = $protocol->pull();
            $this->assertEqualsWithDelta($num, $res[0][0], 0.000001);
        }
    }

    /**
     * @depends      testInit
     * @dataProvider stringProvider
     * @param string $str
     * @param AProtocol $protocol
     */
    public function testString(string $str, AProtocol $protocol)
    {
        $protocol->run('RETURN "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $str) . '" AS a');
        $res = $protocol->pull();
        $this->assertEquals($str, $res[0][0]);
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
     * @param int $size
     * @param AProtocol $protocol
     */
    public function testList(int $size, AProtocol $protocol)
    {
        $protocol->run('RETURN range(0, ' . $size . ') AS a');
        $res = $protocol->pull();
        $this->assertEquals(range(0, $size), $res[0][0]);
    }

    public function listProvider(): \Generator
    {
        foreach ([0, 10, 200, 60000, 200000] as $size)
            yield 'list size: ' . $size => [$size];
    }

    /**
     * @depends      testInit
     * @dataProvider dictionaryProvider
     * @param array $arr
     * @param string $str
     * @param AProtocol $protocol
     */
    public function testDictionary(array $arr, string $str, AProtocol $protocol)
    {
        $protocol->run('RETURN {' . $str . '} AS a');
        $res = $protocol->pull();
        $this->assertEquals($arr, $res[0][0]);
    }

    public function dictionaryProvider(): \Generator
    {
        foreach ([0, 10, 200, 60000, 70000] as $size) {
            $values = [];
            $toString = [];
            for ($i = 0; $i <= $size; $i++) {
                $values['a' . $i] = 1;
                $toString[] = 'a' . $i . ':1';
            }
            yield 'dictionary size: ' . $size => [$values, implode(',', $toString)];
        }
    }

}
