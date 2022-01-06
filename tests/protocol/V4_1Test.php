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
class V4_1Test extends \Bolt\tests\ATest
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
        self::$writeBuffer = [hex2bin('0048b101a48a757365725f6167656e7488626f6c742d70687086736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f7264')];

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
            hex2bin('0048b101a48a757365725f6167656e7488626f6c742d70687086736368656d65856261736963897072696e636970616c84757365728b63726564656e7469616c738870617373776f7264'),
            hex2bin('0002b00e')
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->hello(\Bolt\helpers\Auth::basic('user', 'password'), []);
    }

}
