<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_2;
use Bolt\packstream\v1\{Packer, Unpacker};

/**
 * Class V4_2Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_2
 *
 * @package Bolt\tests\protocol
 */
class V4_2Test extends ATest
{
    /**
     * @return V4_2
     */
    public function test__construct(): V4_2
    {
        $cls = new V4_2(new Packer, new Unpacker, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V4_2::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }

}
