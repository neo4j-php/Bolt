<?php

use Bolt\protocol\Response;
use Bolt\protocol\ServerState;
use Bolt\protocol\V5_1;

/**
 * Class V5_1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
class V5_1Test extends \Bolt\tests\protocol\ATest
{
    public function test__construct(): V5_1
    {
        $cls = new V5_1(1, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V5_1::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testHello(V5_1 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b1',
            '000101',
            '0001a1',
            '000b8a757365725f6167656e74',
            '000988626f6c742d706870',
        ];

        $cls->serverState->set(ServerState::CONNECTED);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $cls->hello()->getSignature());
        $this->assertEquals(ServerState::UNAUTHENTICATED, $cls->serverState->get());

        $cls->serverState->set(ServerState::CONNECTED);
        $response = $cls->hello();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
    }

    /**
     * @depends test__construct
     */
    public function testLogon(V5_1 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b1',
            '00016a',
            '0001a3',
            '000786736368656d65',
            '0006856261736963',
            '000a897072696e636970616c',
            '00058475736572',
            '000c8b63726564656e7469616c73',
            '00098870617373776f7264',
        ];

        $cls->serverState->set(ServerState::UNAUTHENTICATED);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $cls->logon([
            'scheme' => 'basic',
            'principal' => 'user',
            'credentials' => 'password'
        ])->getSignature());
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $cls->serverState->set(ServerState::UNAUTHENTICATED);
        $response = $cls->logon([
            'scheme' => 'basic',
            'principal' => 'user',
            'credentials' => 'password'
        ]);
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
    }

    /**
     * @depends test__construct
     */
    public function testLogoff(V5_1 $cls): void
    {
        self::$readArray = [
            [0x70, (object)[]],
            [0x7F, (object)['message' => 'some error message', 'code' => 'Neo.ClientError.Statement.SyntaxError']]
        ];
        self::$writeBuffer = [
            '0001b0',
            '00016b',
        ];

        $cls->serverState->set(ServerState::READY);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $cls->logoff()->getSignature());
        $this->assertEquals(ServerState::UNAUTHENTICATED, $cls->serverState->get());

        $cls->serverState->set(ServerState::READY);
        $response = $cls->logoff();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
    }
}
