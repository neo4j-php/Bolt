<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_3;
use Bolt\enum\{Signature, ServerState};

/**
 * Class V4_3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
class V4_3Test extends ATest
{
    public function test__construct(): V4_3
    {
        $cls = new V4_3(1, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V4_3::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testRoute(V4_3 $cls): void
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
        ];

        $cls->serverState->set(ServerState::READY);
        $this->assertEquals(Signature::SUCCESS, $cls->route(['address' => 'localhost:7687'])->getResponse()->signature);
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $cls->serverState->set(ServerState::READY);
        $response = $cls->route(['address' => 'localhost:7687'])->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState->get());

        $cls->serverState->set(ServerState::READY);
        $response = $cls->route(['address' => 'localhost:7687'])->getResponse();
        $this->assertEquals(Signature::IGNORED, $response->signature);
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
    }

}
