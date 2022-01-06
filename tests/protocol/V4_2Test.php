<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_2;

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
 * @requires PHP >= 7.1
 * @requires mbstring
 */
class V4_2Test extends \Bolt\tests\ATest
{
    /**
     * @return V4_2
     */
    public function test__construct(): V4_2
    {
        $cls = new V4_2(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
        $this->assertInstanceOf(V4_2::class, $cls);
        return $cls;
    }

}
