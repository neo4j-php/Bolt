<?php

namespace Bolt\tests\protocol;

use PHPUnit\Framework\MockObject\MockObject;
use Bolt\protocol\V1;

/**
 * Class V1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V1
 * @covers \Bolt\PackStream\v1\Packer
 * @covers \Bolt\PackStream\v1\Unpacker
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 * @requires extension mbstring
 */
class V1Test extends \Bolt\tests\ATest
{

    /**
     * Mock Socket class with "write" and "read" methods
     * @return \Bolt\Socket|MockObject
     */
    private function mockSocket()
    {
        $mockBuilder = $this
            ->getMockBuilder(\Bolt\Socket::class)
            ->disableOriginalConstructor();
        call_user_func([$mockBuilder, method_exists($mockBuilder, 'onlyMethods') ? 'onlyMethods' : 'setMethods'], ['write', 'read']);
        /** @var \Bolt\Socket|MockObject $socket */
        $socket = $mockBuilder->getMock();

        $socket
            ->method('write')
            ->with(
                $this->callback(function ($buffer) {
                    $i = self::$writeIndex;
                    self::$writeIndex++;

                    if (self::$writeBuffer)
                        return true;

                    return (self::$writeBuffer[$i] ?? '') == $buffer;
                    //return empty(self::$writeBuffer) ? true : $buffer == self::$writeBuffer;
                })
            );

        $socket
            ->method('read')
            ->will($this->returnCallback([$this, 'readCallback']));

        return $socket;
    }

    /**
     * @var int Internal pointer for "readArray"
     */
    static $readIndex = 0;
    /**
     * @var array Order of consecutive returns from "read" method calls
     */
    static $readArray = [];
    /**
     * @var int Internal pointer for "writeBuffer"
     */
    static $writeIndex = 0;
    /**
     * @var array Expected write buffers or keep empty to skip verification
     */
    static $writeBuffer = [];

    /**
     * Mocked Socket read method
     * @return string
     */
    public function readCallback(): string
    {
        switch (self::$readArray[self::$readIndex]) {
            case 1:
                $output = hex2bin('0003'); // header of length 3
                break;
            case 2:
                $output = hex2bin('B170A0'); // success {}
                break;
            case 3:
                $output = hex2bin('B171A0'); // record {}
                break;
            case 4:
                $output = hex2bin('004b'); // failure header
                break;
            case 5:
                $output = hex2bin('b17fa284636f6465d0254e656f2e436c69656e744572726f722e53746174656d656e742e53796e7461784572726f72876d657373616765d012736f6d65206572726f72206d657373616765'); // failure message
                break;
            default:
                $output = hex2bin('0000'); // end
        }

        self::$readIndex++;
        return (string)$output;
    }

    protected function setUp()
    {
        self::$readIndex = 0;
        self::$readArray = [];
        self::$writeIndex = 0;
        self::$writeBuffer = [];
    }

    /**
     * @return V1
     */
    public function test__construct()
    {
        $cls = new V1(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockSocket());
        $this->assertInstanceOf(V1::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testInit(V1 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('003db20188546573742f312e30a386736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f72640000')];

        $this->assertTrue($cls->init('Test/1.0', 'basic', 'user', 'password'));
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testInitFail(V1 $cls)
    {
        self::$readArray = [4, 5, 0];
        self::$writeBuffer = [
            hex2bin('003db20188546573742f312e30a386736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f72640000'),
            hex2bin('0002b00e0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->init('Test/1.0', 'basic', 'user', 'password');
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testRun(V1 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('000cb2108852455455524e2031900000')];

        $this->assertNotFalse($cls->run('RETURN 1'));
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testRunFail(V1 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('000cb2108852455455524e2031900000'),
            hex2bin('0002b00e0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->run('RETURN 1');
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testPullAll(V1 $cls)
    {
        self::$readArray = [1, 3, 0, 1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b03f0000')];

        $res = $cls->pullAll();
        $this->assertIsArray($res);
        $this->assertCount(2, $res);
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testPullAllFail(V1 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0002b03f0000'),
            hex2bin('0002b00e0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->pullAll();
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testDiscardAll(V1 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b02f0000')];

        $this->assertTrue($cls->discardAll());
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testReset(V1 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b00f0000')];

        $this->assertTrue($cls->reset());
    }

}
