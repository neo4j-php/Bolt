<?php

namespace Bolt\tests\PackStream\v1;

use Bolt\PackStream\v1\Unpacker;
use Exception;

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
class UnpackerTest extends \Bolt\tests\ATest
{

    /**
     * @return Unpacker
     */
    public function test__construct(): Unpacker
    {
        $unpacker = new Unpacker();
        $this->assertInstanceOf(Unpacker::class, $unpacker);
        return $unpacker;
    }

    /**
     * @depends test__construct
     * @dataProvider packProvider It should be unpackProvider but the method name is used as directory name which is same as for PackerTest
     * @param string $bin
     * @param array $arr
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpack(string $bin, array $arr, Unpacker $unpacker)
    {
        $this->assertEquals($arr, $unpacker->unpack(mb_substr($bin, 2)));
        $this->assertEquals(0x88, $unpacker->getSignature());
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
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpackInteger(string $bin, int $number, Unpacker $unpacker)
    {
        $this->assertEquals($number, $unpacker->unpack($bin));
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
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpackFloat(Unpacker $unpacker)
    {
        $this->assertEquals(3.14159, $unpacker->unpack(hex2bin('c1400921f9f01b866e')));
    }

    /**
     * @depends test__construct
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpackNull(Unpacker $unpacker)
    {
        $this->assertEquals(null, $unpacker->unpack(hex2bin('c0')));
    }

    /**
     * @depends test__construct
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpackBool(Unpacker $unpacker)
    {
        $this->assertEquals(true, $unpacker->unpack(hex2bin('c3')));
        $this->assertEquals(false, $unpacker->unpack(hex2bin('c2')));
    }

    /**
     * @depends test__construct
     * @dataProvider stringProvider
     * @param string $bin
     * @param string $str
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpackString(string $bin, string $str, Unpacker $unpacker)
    {
        $this->assertEquals($str, $unpacker->unpack($bin));
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
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpackArray(string $bin, array $arr, Unpacker $unpacker)
    {
        $this->assertEquals($arr, $unpacker->unpack($bin));
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
     * @param Unpacker $unpacker
     * @throws Exception
     */
    public function testUnpackMap(string $bin, $obj, Unpacker $unpacker)
    {
        $this->assertEquals($obj, $unpacker->unpack($bin));
    }

    /**
     * @return array
     */
    public function mapProvider(): array
    {
        $data = $this->provider(__FUNCTION__);
        foreach ($data as &$entry)
            $entry[1] = json_decode($entry[1], true);
        return $data;
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