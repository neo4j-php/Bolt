<?php

namespace Bolt\tests\protocol;

use Bolt\error\IgnoredException;
use Bolt\protocol\ServerState;
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
 */
class V3Test extends ATest
{
    /**
     * @return V3
     */
    public function test__construct(): V3
    {
        $cls = new V3(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection(), new ServerState());
        $this->assertInstanceOf(V3::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testHello(V3 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b1',
            '000101',
            '0001a4',
            '000b8a757365725f6167656e74',
            '000988626f6c742d706870',
            '000786736368656d65',
            '0006856261736963',
            '000a897072696e636970616c',
            '00058475736572',
            '000c8b63726564656e7469616c73',
            '00098870617373776f7264',

            '0001b1',
            '000101',
            '0001a4',
            '000b8a757365725f6167656e74',
            '000988626f6c742d706870',
            '000786736368656d65',
            '0006856261736963',
            '000a897072696e636970616c',
            '00058475736572',
            '000c8b63726564656e7469616c73',
            '00098870617373776f7264',
            '0002b00e',
        ];

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $this->assertIsArray($cls->hello(\Bolt\helpers\Auth::basic('user', 'password')));
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::CONNECTED);
            $cls->hello(\Bolt\helpers\Auth::basic('user', 'password'));
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRun(V3 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b3',
            '000110',
            '00098852455455524e2031',
            '0001a0',
            '0001a0',

            '00 01 b3',
            '00 01 10',
            '00 0a 89 6e    6f 74 20 61    20 43 51 4c',
            '00 01 a0',
            '00 01 a0',

            '00 01 b3',
            '00 01 10',
            '00 0a 89 6e    6f 74 20 61    20 43 51 4c',
            '00 01 a0',
            '00 01 a0',
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
     * @param V3 $cls
     */
    public function testBegin(V3 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b1',
            '000111',
            '0001a0',

            '0001b1',
            '000111',
            '0001a0',

            '00 01 b1',
            '00 01 11',
            '00 01 a0',
        ];

        try {
            $cls->serverState->set(ServerState::READY);
            $this->assertIsArray($cls->begin());
            $this->assertEquals(ServerState::TX_READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::READY);
            $cls->begin();
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::READY);
            $cls->begin();
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testCommit(V3 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b0',
            '000112',
            '0001b0',
            '00 01 12',
            '00 01 b0',
            '00 01 12',
        ];

        try {
            $cls->serverState->set(ServerState::TX_READY);
            $this->assertIsArray($cls->commit());
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::TX_READY);
            $cls->commit();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::TX_READY);
            $cls->commit();
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testRollback(V3 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b0',
            '000113',
            '0001b0',
            '00 01 13',
            '00 01 b0',
            '00 01 13',
        ];

        try {
            $cls->serverState->set(ServerState::TX_READY);
            $this->assertIsArray($cls->rollback());
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::TX_READY);
            $cls->rollback();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::TX_READY);
            $cls->rollback();
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V3 $cls
     */
    public function testGoodbye(V3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [
            '0001b0',
            '000102',
        ];

        try {
            $cls->goodbye();
            $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

}
