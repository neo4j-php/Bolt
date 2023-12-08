<?php

use Bolt\protocol\Response;
use Bolt\protocol\ServerState;
use Bolt\protocol\V5_4;

/**
 * Class V5_4Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
class V5_4Test extends \Bolt\tests\protocol\ATest
{
    public function test__construct(): V5_4
    {
        $cls = new V5_4(1, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V5_4::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

    /**
     * @depends test__construct
     */
    public function testTelemetry(V5_4 $cls): void
    {
        // todo
    }
}
