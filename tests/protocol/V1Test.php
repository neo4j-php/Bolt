<?php

namespace Bolt\tests\protocol;

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
     * @return V1
     */
    public function test__construct()
    {
        $cls = new V1(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
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

        $this->assertIsArray($cls->init('Test/1.0', 'basic', 'user', 'password'));
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
