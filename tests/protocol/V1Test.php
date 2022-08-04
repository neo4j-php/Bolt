<?php

namespace Bolt\tests\protocol;

use Bolt\error\IgnoredException;
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
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b2',
            '000101',
            '000988626f6c742d706870',
            '0001a3',
            '000786736368656d65',
            '0006856261736963',
            '000a897072696e636970616c',
            '00058475736572',
            '000c8b63726564656e7469616c73',
            '00098870617373776f7264',

            '0001b2',
            '000101',
            '000988626f6c742d706870',
            '0001a3',
            '000786736368656d65',
            '0006856261736963',
            '000a897072696e636970616c',
            '00058475736572',
            '000c8b63726564656e7469616c73',
            '00098870617373776f7264',
            '0002b00e0000',
        ];

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $this->assertIsArray($cls->init(\Bolt\helpers\Auth::basic('user', 'password')));
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $cls->init(\Bolt\helpers\Auth::basic('user', 'password'));
        } catch (Exception $e) {
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
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b2',
            '000110',
            '00098852455455524e2031',
            '0001a0',

            '00 01 b2',
            '00 01 10',
            '00 0a 89 6e    6f 74 20 61    20 43 51 4c',
            '00 01 a0',

            '00 01 b2',
            '00 01 10',
            '00 0a 89 6e    6f 74 20 61    20 43 51 4c',
            '00 01 a0'
        ];

        try {
            $cls->serverState->set(ServerState::READY);
            $this->assertIsArray($cls->run('RETURN 1'));
            $this->assertEquals(ServerState::STREAMING, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::READY);
            $cls->run('not a CQL');
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::READY);
            $cls->run('not a CQL');
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testPullAll(V1 $cls)
    {
        self::$readArray = [
            [0x71, (object)[]],
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b0',
            '00013f',

            '0001b0',
            '00013f',

            '0001b0',
            '00013f',
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

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->pullAll();
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->pullAll();
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testDiscardAll(V1 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b0',
            '00012f',

            '0001b0',
            '00012f',

            '0001b0',
            '00012f',
        ];

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->discardAll();
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->discardAll();
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->discardAll();
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testReset(V1 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b0',
            '00010f',

            '0001b0',
            '00010f',
        ];

        try {
            $cls->reset();
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->reset();
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V1 $cls
     */
    public function testAckFailure(V1 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b0',
            '00010e',

            '0001b0',
            '00010e',
        ];

        try {
            $cls->ackFailure();
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->ackFailure();
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
        }
    }
}
