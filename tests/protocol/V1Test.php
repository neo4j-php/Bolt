<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\Response;
use Bolt\protocol\ServerState;
use Bolt\protocol\V1;
use Bolt\packstream\v1\{Packer, Unpacker};

/**
 * Class V1Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V1
 * @covers \Bolt\packstream\v1\Packer
 * @covers \Bolt\packstream\v1\Unpacker
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
        $cls = new V1(new Packer, new Unpacker, $this->mockConnection(), new ServerState());
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
        ];

        $auth = \Bolt\helpers\Auth::basic('user', 'password');
        unset($auth['user_agent']);

        $cls->serverState->set(ServerState::CONNECTED);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $cls->init(\Bolt\helpers\Auth::$defaultUserAgent, $auth)->getSignature());
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $cls->serverState->set(ServerState::CONNECTED);
        $response = $cls->init(\Bolt\helpers\Auth::$defaultUserAgent, $auth);
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
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

        $cls->serverState->set(ServerState::READY);
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $cls->run('RETURN 1')->getResponse()->getSignature());
        $this->assertEquals(ServerState::STREAMING, $cls->serverState->get());

        $cls->serverState->set(ServerState::READY);
        $response = $cls->run('not a CQL')->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState->get());

        $cls->serverState->set(ServerState::READY);
        $response = $cls->run('not a CQL')->getResponse();
        $this->assertEquals(Response::SIGNATURE_IGNORED, $response->getSignature());
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
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
        ];

        $cls->serverState->set(ServerState::STREAMING);
        $res = iterator_to_array($cls->pullAll()->getResponses(), false);
        $this->assertIsArray($res);
        $this->assertCount(2, $res);
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $cls->serverState->set(ServerState::STREAMING);
        $responses = iterator_to_array($cls->pullAll()->getResponses(), false);
        $this->checkFailure($responses[0]);
        $this->assertEquals(ServerState::FAILED, $cls->serverState->get());

        $cls->serverState->set(ServerState::STREAMING);
        $responses = iterator_to_array($cls->pullAll()->getResponses(), false);
        $this->assertEquals(Response::SIGNATURE_IGNORED, $responses[0]->getSignature());
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
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
        ];

        $cls->serverState->set(ServerState::STREAMING);
        $cls->discardAll()->getResponse();
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $cls->serverState->set(ServerState::STREAMING);
        $response = $cls->discardAll()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::FAILED, $cls->serverState->get());

        $cls->serverState->set(ServerState::STREAMING);
        $response = $cls->discardAll()->getResponse();
        $this->assertEquals(Response::SIGNATURE_IGNORED, $response->getSignature());
        $this->assertEquals(ServerState::INTERRUPTED, $cls->serverState->get());
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
        ];

        $cls->reset()->getResponse();
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $response = $cls->reset()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
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
        ];

        $cls->serverState->set(ServerState::FAILED);
        $cls->ackFailure()->getResponse();
        $this->assertEquals(ServerState::READY, $cls->serverState->get());

        $cls->serverState->set(ServerState::FAILED);
        $response = $cls->ackFailure()->getResponse();
        $this->checkFailure($response);
        $this->assertEquals(ServerState::DEFUNCT, $cls->serverState->get());
    }
}
