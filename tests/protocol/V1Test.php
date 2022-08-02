<?php

namespace Bolt\tests\protocol;

use Bolt\error\MessageException;
use Bolt\protocol\ServerState;
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
 */
class V1Test extends ATest
{

    /**
     * @return V1
     */
    public function test__construct(): V1
    {
        $cls = new V1(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection(), new ServerState());
        $this->assertInstanceOf(V1::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testInit(V1 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]]
        ];
        self::$writeBuffer = [
            hex2bin('0001b2'),
            hex2bin('000101'),
            hex2bin('000988626f6c742d706870'),
            hex2bin('0001a3'),
            hex2bin('000786736368656d65'),
            hex2bin('0006856261736963'),
            hex2bin('000a897072696e636970616c'),
            hex2bin('00058475736572'),
            hex2bin('000c8b63726564656e7469616c73'),
            hex2bin('00098870617373776f7264'),
        ];

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $this->assertIsArray($cls->init(\Bolt\helpers\Auth::basic('user', 'password')));
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
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
        self::$readArray = [
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            hex2bin('0001b2'),
            hex2bin('000101'),
            hex2bin('000988626f6c742d706870'),
            hex2bin('0001a3'),
            hex2bin('000786736368656d65'),
            hex2bin('0006856261736963'),
            hex2bin('000a897072696e636970616c'),
            hex2bin('00058475736572'),
            hex2bin('000c8b63726564656e7469616c73'),
            hex2bin('00098870617373776f7264'),
            hex2bin('0002b00e0000')
        ];

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $cls->init(\Bolt\helpers\Auth::basic('user', 'password'));
        } catch (MessageException $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testRun(V1 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]]
        ];
        self::$writeBuffer = [
            hex2bin('0001b2'),
            hex2bin('000110'),
            hex2bin('00098852455455524e2031'),
            hex2bin('0001a0'),
        ];

        try {
            $cls->serverState->set(ServerState::READY);
            $this->assertIsArray($cls->run('RETURN 1'));
            $this->assertEquals(ServerState::STREAMING, $cls->serverState->get());
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
        self::$readArray = [
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x70, (object)[]]
        ];
        self::$writeBuffer = [
            hex2bin('0001b2'),
            hex2bin('000110'),
            hex2bin('00098852455455524e2031'),
            hex2bin('0001a0'),
            hex2bin('0001b0'),
            hex2bin('00010e')
        ];

        try {
            $cls->serverState->set(ServerState::READY);
            $cls->run('RETURN 1');
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }
    }

    // @todo add runIgnored

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testPullAll(V1 $cls)
    {
        self::$readArray = [
            [0x71, (object)[]],
            [0x70, (object)[]]
        ];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('00013f'),
        ];

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $res = $cls->pullAll();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        $this->assertIsArray($res);
        $this->assertCount(2, $res);
        $this->assertEquals(ServerState::READY, $cls->serverState->get());
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testPullAllFail(V1 $cls)
    {
        self::$readArray = [
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x70, (object)[]]
        ];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('00013f'),
            hex2bin('0001b0'),
            hex2bin('00010e')
        ];

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->pullAll();
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }
    }

    // @todo add pullAll ignored

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testDiscardAll(V1 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]]
        ];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('00012f'),
        ];

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->discardAll();
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    // @todo add discardAll failed and ignored

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testReset(V1 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]]
        ];
        self::$writeBuffer = [
            hex2bin('0001b0'),
            hex2bin('00010f'),
        ];

        try {
            $cls->reset();
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    // @todo add reset failed

    // @todo ackFailure success and fail

}
