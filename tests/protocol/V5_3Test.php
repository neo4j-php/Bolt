<?php

use Bolt\protocol\Response;
use Bolt\protocol\ServerState;
use Bolt\protocol\V5_3;

/**
 * Class V5_3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
class V5_3Test extends \Bolt\tests\protocol\ATest
{
    public function test__construct(): V5_3
    {
        $cls = new V5_3(1, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V5_3::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testHello(V5_3 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];

        $cls->serverState->set(ServerState::CONNECTED);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $cls->hello()->getSignature());
        $this->assertEquals(ServerState::AUTHENTICATION, $cls->serverState->get());

        $cls->serverState->set(ServerState::CONNECTED);
        $response = $cls->hello();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
    }
}
