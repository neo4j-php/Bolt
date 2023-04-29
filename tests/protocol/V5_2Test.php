<?php

use Bolt\protocol\V5_2;

/**
 * Class V5_2Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
class V5_2Test extends \Bolt\tests\protocol\ATest
{
    public function test__construct(): V5_2
    {
        $cls = new V5_2(1, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V5_2::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }
}
