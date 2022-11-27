<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_2;
use Bolt\packstream\v1\{Packer, Unpacker};

/**
 * Class V4_2Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests\protocol
 */
class V4_2Test extends ATest
{
    public function test__construct(): V4_2
    {
        $cls = new V4_2(1, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V4_2::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

}
