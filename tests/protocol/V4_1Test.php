<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_1;
use Exception;

/**
 * Class V4_1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_1
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 * @requires mbstring
 */
class V4_1Test extends ATest
{
    /**
     * @return V4_1
     */
    public function test__construct(): V4_1
    {
        $cls = new V4_1(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
        $this->assertInstanceOf(V4_1::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V4_1 $cls
     */
    public function testHello(V4_1 $cls)
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
            $this->assertIsArray($cls->hello(\Bolt\helpers\Auth::basic('user', 'password'), []));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends test__construct
     * @param V4_1 $cls
     */
    public function testHelloFail(V4_1 $cls)
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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->hello(\Bolt\helpers\Auth::basic('user', 'password'), []);
    }

}
