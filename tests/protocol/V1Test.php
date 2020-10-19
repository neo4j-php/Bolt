<?php

namespace Bolt\tests\cls;

use PHPUnit\Framework\TestCase;
use Bolt\protocol\V1;

/**
 * Class V1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 *
 * @covers \Bolt\protocol\V1
 *
 * @package Bolt\tests\cls
 * @requires PHP >= 7.1
 * @requires extension mbstring
 */
class V1Test extends TestCase
{
    private static $clsSocketPath = __DIR__ . DS . '..' . DS . '..' . DS . 'Socket.php';

    public static function setUpBeforeClass(): void
    {
        $content = file_get_contents(self::$clsSocketPath);
        if (preg_match("/^final class Socket/m", $content))
            file_put_contents(self::$clsSocketPath, str_replace('final class Socket', 'class Socket', $content));
    }

    public static function tearDownAfterClass(): void
    {
        $content = file_get_contents(self::$clsSocketPath);
        if (preg_match("/^class Socket/m", $content))
            file_put_contents(self::$clsSocketPath, str_replace('class Socket', 'final class Socket', $content));
    }

    /**
     * @return V1
     */
    public function test__construct()
    {
        $socket = $this->getMockBuilder(\Bolt\Socket::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['write', 'read'])
            ->getMock();

        $socket
            ->method('write')
            ->with(
                $this->callback(function ($buffer) {
                    return $buffer == self::$buffer;
                })
            );

        $socket
            ->method('read')
            ->will($this->returnCallback([$this, 'readCallback']));

        $cls = new V1(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $socket);
        $this->assertInstanceOf(V1::class, $cls);

        return $cls;
    }

    static $i = 0;
    static $buffer = '';

    public function readCallback()
    {
        $output = '';
        switch (self::$i) {
            case 0:
                $output = hex2bin('0003'); // header of length 3
                break;
            case 1:
                $output = hex2bin('B170A0'); // success {}
                break;
            case 2:
                $output = hex2bin('0000'); // end
                break;

            case 3:

                break;
        }

        self::$i++;
        return $output;
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testInit(V1 $cls)
    {
        self::$i = 0;
        self::$buffer = 0x001fb40188546573742f312e3085626173696384757365728870617373776f72640000;
        $this->assertTrue($cls->init('Test/1.0', 'basic', 'user', 'password'));
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testRun(V1 $cls)
    {
        self::$i = 0;
        $this->assertNotFalse($cls->run('RETURN 1'));
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testPullAll(V1 $cls)
    {
        self::$i = 3;
    }

}
