<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V2;

/**
 * Class V2Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V2
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 */
class V2Test extends \Bolt\tests\ATest
{
    /**
     * @return V2
     */
    public function test__construct(): V2
    {
        $cls = new V2(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
        $this->assertInstanceOf(V2::class, $cls);
        return $cls;
    }
}
