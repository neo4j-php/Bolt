<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V3;

/**
 * Class V3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V3
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 * @requires mbstring
 */
class V3Test extends \Bolt\tests\ATest
{
    /**
     * @return V3
     */
    public function test__construct()
    {
        $cls = new V3(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
        $this->assertInstanceOf(V3::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testHello(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0048b101a48a757365725f6167656e7488546573742f312e3086736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f72640000')];

        $this->assertIsArray($cls->hello('Test/1.0', 'basic', 'user', 'password'));
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testHelloFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0];
        self::$writeBuffer = [
            hex2bin('0048b101a48a757365725f6167656e7488546573742f312e3086736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f72640000'),
            hex2bin('0002b00e0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->hello('Test/1.0', 'basic', 'user', 'password');
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRun(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('000db3108852455455524e2031a0a00000')];

        $this->assertNotFalse($cls->run('RETURN 1'));
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRunFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('000db3108852455455524e2031a0a00000'),
            hex2bin('0002b00f0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->run('RETURN 1');
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testReset(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b00f0000')];

        $this->assertTrue($cls->reset());
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testBegin(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0003b111a00000')];

        $this->assertTrue($cls->begin());
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testBeginFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0003b111a00000'),
            hex2bin('0002b00f0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->begin();
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testCommit(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b0120000')];

        $this->assertTrue($cls->commit());
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testCommitFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0002b0120000'),
            hex2bin('0002b00f0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->commit();
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRollback(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b0130000')];

        $this->assertTrue($cls->rollback());
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRollbackFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0002b0130000'),
            hex2bin('0002b00f0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->rollback();
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testGoodbye(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b0020000')];

        $this->assertNull($cls->goodbye());
    }

}
