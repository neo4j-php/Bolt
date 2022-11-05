<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V5_1;

/**
 * Class V5Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V5_1
 *
 * @package Bolt\tests\protocol
 */
class V5_1Test extends ATest
{
    /**
     * @return V5_1
     */
    public function test__construct(): V5_1
    {
        $cls = new V5_1(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection(), new \Bolt\protocol\ServerState());
        $this->assertInstanceOf(V5_1::class, $cls);
        $cls->serverState->expectedServerStateMismatchCallback = function (string $current, array $expected) {
            $this->markTestIncomplete('Server in ' . $current . ' state. Expected ' . implode(' or ', $expected) . '.');
        };
        return $cls;
    }
}
