<?php

namespace Bolt\tests\protocol;

use Bolt\error\IgnoredException;
use Bolt\protocol\ServerState;
use Bolt\protocol\V4;
use Exception;

/**
 * Class V4Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4
 *
 * @package Bolt\tests\protocol
 */
class V4Test extends ATest
{
    /**
     * @return V4
     */
    public function test__construct(): V4
    {
        $cls = new V4(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection(), new ServerState());
        $this->assertInstanceOf(V4::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V4 $cls
     */
    public function testPull(V4 $cls)
    {
        self::$readArray = [
            [0x71, (object)[]],
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '00 01 b1',
            '00 01 3f',
            '00 01 a2',
            '00 02 81 6e',
            '00 01 ff',
            '00 04 83 71    69 64',
            '00 01 ff',

            '00 01 b1',
            '00 01 3f',
            '00 01 a2',
            '00 02 81 6e',
            '00 01 ff',
            '00 04 83 71    69 64',
            '00 01 ff',

            '00 01 b1',
            '00 01 3f',
            '00 01 a2',
            '00 02 81 6e',
            '00 01 ff',
            '00 04 83 71    69 64',
            '00 01 ff',
        ];

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $res = $cls->pull(['n' => -1, 'qid' => -1]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        $this->assertIsArray($res);
        $this->assertCount(2, $res);
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $res = $cls->pull(['n' => -1, 'qid' => -1]);
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $res = $cls->pull(['n' => -1, 'qid' => -1]);
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

    /**
     * @depends test__construct
     * @param V4 $cls
     */
    public function testDiscard(V4 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b1',
            '00012f',
            '0001a2',
            '0002816e',
            '0001ff',
            '000483716964',
            '0001ff',

            '0001b1',
            '00012f',
            '0001a2',
            '0002816e',
            '0001ff',
            '000483716964',
            '0001ff',

            '0001b1',
            '00012f',
            '0001a2',
            '0002816e',
            '0001ff',
            '000483716964',
            '0001ff',
        ];

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $this->assertIsArray($cls->discard(['n' => -1, 'qid' => -1]));
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->discard(['n' => -1, 'qid' => -1]);
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::STREAMING);
            $cls->discard(['n' => -1, 'qid' => -1]);
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }
}
