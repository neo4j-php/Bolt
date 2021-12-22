<?php

namespace Bolt\tests\PackStream\v1;

use Bolt\PackStream\v1\Packer;
use Exception;

/**
 * Class PackerTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\PackStream\v1\Packer
 *
 * @package Bolt\tests\PackStream\v1
 * @requires PHP >= 7.1
 * @requires extension mbstring
 * @requires extension json
 */
class PackerTest extends \Bolt\tests\ATest
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
     * @dataProvider packProvider
     * @param string $bin
     * @param array $args
     * @param Packer $packer
     * @throws Exception
     */
    public function testPack(string $bin, array $args, Packer $packer)
    {
        $this->assertEquals($bin, implode(iterator_to_array($packer->pack(0x88, $args))));
    }

    /**
     * @return array
     */
    public function packProvider(): array
    {
        $data = $this->provider(__FUNCTION__);
        foreach ($data as &$entry)
            $entry[1] = json_decode($entry[1], true);
        return $data;
    }

    /**
     * @depends test__construct
     * @dataProvider integerProvider
     * @param string $bin
     * @param int $number
     * @param Packer $packer
     * @throws Exception
     */
    public function testPackInteger(string $bin, int $number, Packer $packer)
    {
        $this->assertEquals($bin, $this->getMethod($packer)->invoke($packer, $number));
    }

    /**
     * @return array
     */
    public function integerProvider(): array
    {
        $data = $this->provider(__FUNCTION__);
        foreach ($data as &$entry)
            $entry[1] = intval($entry[1]);
        return $data;
    }

    /**
     * @depends test__construct
     * @param Packer $packer
     * @throws Exception
     */
    public function testPackFloat(Packer $packer)
    {
        $this->assertEquals('c1400921f9f01b866e', bin2hex($this->getMethod($packer)->invoke($packer, 3.14159)));
    }

    /**
     * @depends test__construct
     * @param Packer $packer
     * @throws Exception
     */
    public function testPackNull(Packer $packer)
    {
        $this->assertEquals('c0', bin2hex($this->getMethod($packer)->invoke($packer, null)));
    }

    /**
     * @depends test__construct
     * @param Packer $packer
     * @throws Exception
     */
    public function testPackBool(Packer $packer)
    {
        $this->assertEquals('c2', bin2hex($this->getMethod($packer)->invoke($packer, false)));
        $this->assertEquals('c3', bin2hex($this->getMethod($packer)->invoke($packer, true)));
    }

    /**
     * @depends test__construct
     * @dataProvider stringProvider
     * @param string $bin
     * @param string $str
     * @param Packer $packer
     * @throws Exception
     */
    public function testPackString(string $bin, string $str, Packer $packer)
    {
        $this->assertEquals($bin, $this->getMethod($packer)->invoke($packer, $str));
    }

    /**
     * @return array
     */
    public function stringProvider(): array
    {
        return $this->provider(__FUNCTION__);
    }

    /**
     * @depends test__construct
     * @dataProvider arrayProvider
     * @param string $bin
     * @param array $arr
     * @param Packer $packer
     * @throws Exception
     */
    public function testPackArray(string $bin, array $arr, Packer $packer)
    {
        $this->assertEquals($bin, $this->getMethod($packer)->invoke($packer, $arr));
    }

    /**
     * @return array
     */
    public function arrayProvider(): array
    {
        $data = $this->provider(__FUNCTION__);
        foreach ($data as &$entry)
            $entry[1] = array_map('intval', explode(',', $entry[1]));
        return $data;
    }

    /**
     * @depends test__construct
     * @dataProvider mapProvider
     * @param string $bin
     * @param object $obj
     * @param Packer $packer
     * @throws Exception
     */
    public function testPackMap(string $bin, $obj, Packer $packer)
    {
        $this->assertEquals($bin, $this->getMethod($packer)->invoke($packer, $obj));
    }

    /**
     * @return array
     */
    public function mapProvider(): array
    {
        $data = $this->provider(__FUNCTION__);
        foreach ($data as &$entry)
            $entry[1] = json_decode($entry[1]);
        return $data;
    }

    /**
     * Test it on data type resource, which is not implemented
     * @depends test__construct
     * @param Packer $packer
     * @throws Exception
     */
    public function testException(Packer $packer)
    {
        $f = fopen(__FILE__, 'r');
        $this->expectException(Exception::class);
        $this->getMethod($packer)->invoke($packer, $f);
        fclose($f);
    }


    /**
     * Get method from Packer as accessible
     * @param Packer $packer
     * @return \ReflectionMethod
     */
    private function getMethod(Packer $packer): \ReflectionMethod
    {
        $reflection = new \ReflectionClass(get_class($packer));
        $method = $reflection->getMethod('p');
        $method->setAccessible(true);
        return $method;
    }

    /**
     * "Abstract" provider to read content of directory as provider array
     * @param string $fnc
     * @return array
     */
    private function provider(string $fnc): array
    {
        $output = [];
        $path = __DIR__ . DS . $fnc . DS;

        foreach (scandir($path) as $file) {
            $file_parts = pathinfo($file);
            switch ($file_parts['extension']) {
                case 'bin':
                    $output[$file_parts['filename']][0] = file_get_contents($path . $file);
                    break;
                case 'txt':
                    $output[$file_parts['filename']][1] = trim(file_get_contents($path . $file));
                    break;
            }
        }

        return $output;
    }

}
