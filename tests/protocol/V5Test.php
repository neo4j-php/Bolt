<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V5;
use Bolt\packstream\v1\{Packer, Unpacker};

/**
 * Class V5Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V5
 *
 * @package Bolt\tests\protocol
 */
class V5Test extends ATest
{
    /**
     * @return V5
     */
    public function test__construct(): V5
    {
        $cls = new V5(new Packer, new Unpacker, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V5::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }
}
