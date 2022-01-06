<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V1;
use Exception;

/**
 * Class V1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
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
    public function test__construct(): V1
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
        self::$writeBuffer = [hex2bin('003db20188626f6c742d706870a386736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f7264')];

        try {
            $this->assertIsArray($cls->init(\Bolt\helpers\Auth::basic('user', 'password')));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testInitFail(V1 $cls)
    {
        self::$readArray = [4, 5, 0];
        self::$writeBuffer = [
            hex2bin('003db20188626f6c742d706870a386736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f7264'),
            hex2bin('0002b00e0000')
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->init(\Bolt\helpers\Auth::basic('user', 'password'));
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testRun(V1 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('000cb2108852455455524e2031a0')];

        try {
            $this->assertIsArray($cls->run('RETURN 1'));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testRunFail(V1 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('000cb2108852455455524e2031a0'),
            hex2bin('0002b00e')
        ];

        $this->expectException(Exception::class);
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
        self::$writeBuffer = [hex2bin('0002b03f')];

        try {
            $res = $cls->pullAll();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

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
            hex2bin('0002b03f'),
            hex2bin('0002b00e')
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->pullAll();
    }

    /**
     * @doesNotPerformAssertions
     * @depends test__construct
     * @param V1 $cls
     */
    public function testDiscardAll(V1 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b02f')];

        try {
            $cls->discardAll();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @doesNotPerformAssertions
     * @depends test__construct
     * @param V1 $cls
     */
    public function testReset(V1 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('0002b00f')];

        try {
            $cls->reset();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

}
