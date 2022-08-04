<?php

namespace Bolt\tests\protocol;

use Bolt\error\IgnoredException;
use Bolt\protocol\ServerState;
use Bolt\protocol\V4_3;
use Exception;

/**
 * Class V4_3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_3
 *
 * @package Bolt\tests\protocol
 */
class V4_3Test extends ATest
{
    /**
     * @return V4_3
     */
    public function test__construct(): V4_3
    {
        $cls = new V4_3(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V4_3::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V4_3 $cls
     */
    public function testRoute(V4_3 $cls)
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']],
            [0x7E, (object)[]]
        ];
        self::$writeBuffer = [
            '0001b3',
            '000166',
            '0001a1',
            '00088761646472657373',
            '000f8e6c6f63616c686f73743a37363837',
            '000190',
            '0001c0',

            '0001b3',
            '000166',
            '0001a1',
            '00088761646472657373',
            '000f8e6c6f63616c686f73743a37363837',
            '000190',
            '0001c0',

            '0001b3',
            '000166',
            '0001a1',
            '00088761646472657373',
            '000f8e6c6f63616c686f73743a37363837',
            '000190',
            '0001c0',
        ];

        try {
            $cls->serverState->set(ServerState::READY);
            $this->assertIsArray($cls->route(['address' => 'localhost:7687'], [], null));
            $this->assertEquals(ServerState::READY, $cls->serverState->get());
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        try {
            $cls->serverState->set(ServerState::READY);
            $cls->route(['address' => 'localhost:7687'], [], null);
        } catch (Exception $e) {
            $this->assertEquals('some error message (Neo.ClientError.Statement.SyntaxError)', $e->getMessage());
            $this->assertEquals(ServerState::FAILED, $cls->serverState->get());
        }

        try {
            $cls->serverState->set(ServerState::READY);
            $cls->route(['address' => 'localhost:7687'], [], null);
        } catch (Exception $e) {
            $this->assertInstanceOf(IgnoredException::class, $e);
            $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
        }
    }

}
