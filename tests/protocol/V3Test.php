<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V3;
use Exception;

/**
 * Class V3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V3
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 * @requires mbstring
 */
class V3Test extends ATest
{
    /**
     * @return V3
     */
    public function test__construct(): V3
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
        self::$writeBuffer = [
            hex2bin('0001b1'),
            hex2bin('000101'),
            hex2bin('0001a4'),
            hex2bin('000b8a757365725f6167656e74'),
            hex2bin('000988626f6c742d706870'),
            hex2bin('000786736368656d65'),
            hex2bin('0006856261736963'),
            hex2bin('000a897072696e636970616c'),
            hex2bin('00058475736572'),
            hex2bin('000c8b63726564656e7469616c73'),
            hex2bin('00098870617373776f7264'),
        ];

        try {
            $this->assertIsArray($cls->hello(\Bolt\helpers\Auth::basic('user', 'password')));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testHelloFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0];
        self::$writeBuffer = [
            hex2bin('0001b1'),
            hex2bin('000101'),
            hex2bin('0001a4'),
            hex2bin('000b8a757365725f6167656e74'),
            hex2bin('000988626f6c742d706870'),
            hex2bin('000786736368656d65'),
            hex2bin('0006856261736963'),
            hex2bin('000a897072696e636970616c'),
            hex2bin('00058475736572'),
            hex2bin('000c8b63726564656e7469616c73'),
            hex2bin('00098870617373776f7264'),
            hex2bin('0002b00e')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->hello(\Bolt\helpers\Auth::basic('user', 'password'));
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRun(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b3'),
            hex2bin('000110'),
            hex2bin('00098852455455524e2031'),
            hex2bin('0001a0'),
            hex2bin('0001a0'),
        ];

        try {
            $this->assertIsArray($cls->run('RETURN 1'));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRunFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b3'),
            hex2bin('000110'),
            hex2bin('00098852455455524e2031'),
            hex2bin('0001a0'),
            hex2bin('0001a0'),
            hex2bin('0002b00f0000')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->run('RETURN 1');
    }

    /**
     * @doesNotPerformAssertions
     * @depends test__construct
     * @param V3 $cls
     */
    public function testReset(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('00010f'),
        ];

        try {
            $cls->reset();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testBegin(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b1'),
            hex2bin('000111'),
            hex2bin('0001a0'),
        ];

        try {
            $this->assertIsArray($cls->begin());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testBeginFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b1'),
            hex2bin('000111'),
            hex2bin('0001a0'),
            hex2bin('0002b00f')
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
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('000112'),
        ];

        try {
            $this->assertIsArray($cls->commit());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testCommitFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('000112'),
            hex2bin('0002b00f')
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
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('000113'),
        ];

        try {
            $this->assertIsArray($cls->rollback());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRollbackFail(V3 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('000113'),
            hex2bin('0002b00f')
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->rollback();
    }

    /**
     * @doesNotPerformAssertions
     * @depends test__construct
     * @param V3 $cls
     */
    public function testGoodbye(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('000102'),
        ];

        try {
            $cls->goodbye();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

}
