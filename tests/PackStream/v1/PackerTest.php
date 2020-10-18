<?php

namespace Bolt\tests\PackStream\v1;

use Bolt\PackStream\v1\Packer;
use PHPUnit\Framework\TestCase;

/**
 * Class PackerTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 *
 * @covers \Bolt\PackStream\v1\Packer
 *
 * @package Bolt\tests\PackStream\v1
 * @requires PHP >= 7.1
 * @requires extension mbstring
 * @requires extension json
 */
class PackerTest extends TestCase
{

    /**
     * @return Packer
     */
    public function test__construct(): Packer
    {
        $packer = new Packer();
        $this->assertInstanceOf(Packer::class, $packer);
        return $packer;
    }

    /**
     * @depends test__construct
     * @dataProvider integerProvider
     * @param string $hex
     * @param int $number
     * @param Packer $packer
     * @throws \Exception
     */
    public function testPackInteger(string $hex, int $number, Packer $packer)
    {
        $reflection = new \ReflectionClass(get_class($packer));
        $method = $reflection->getMethod('packInteger');
        $method->setAccessible(true);

        $this->assertEquals($hex, $this->toHex($method->invoke($packer, $number)));
    }

    /**
     * @return array
     */
    public function integerProvider(): array
    {
        $data = [
            ['05', 5],
            ['fb', -5],
            ['c8ec', -20],
            ['c90800', 2048],
            ['ca0000dac0', 56000]
        ];

        if (PHP_INT_MAX > 2147483647)
            array_push($data, ['cb00000000fffffffe', 2147483647 * 2]);

        return $data;
    }

    /**
     * @depends test__construct
     * @param Packer $packer
     * @throws \Exception
     */
    public function testPackFloat(Packer $packer)
    {
        $reflection = new \ReflectionClass(get_class($packer));
        $method = $reflection->getMethod('packFloat');
        $method->setAccessible(true);

        $this->assertEquals('c1400921f9f01b866e', $this->toHex($method->invoke($packer, 3.14159)));
    }

    /**
     * @depends test__construct
     * @dataProvider stringProvider
     * @param string $hex
     * @param string $str
     * @param Packer $packer
     * @throws \Exception
     */
    public function testPackString(string $hex, string $str, Packer $packer)
    {
        $reflection = new \ReflectionClass(get_class($packer));
        $method = $reflection->getMethod('packString');
        $method->setAccessible(true);

        $this->assertEquals($hex, $this->toHex($method->invoke($packer, $str)));
    }

    /**
     * @return array
     */
    public function stringProvider(): array
    {
        $rows = array_filter(file('strings.txt'));
        if (empty($rows) || count($rows) % 2 == 1)
            return [];

        $output = [];
        for ($i = 0; $i < count($rows); $i += 2) {
            array_push($output, [
                trim($rows[$i]),
                trim($rows[$i + 1])
            ]);
        }

        return $output;
    }

    /**
     * @param string $str
     * @return string
     */
    private function toHex(string $str): string
    {
        return implode(unpack('H*', $str));
    }

}
