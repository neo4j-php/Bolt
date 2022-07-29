<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V2;
use Exception;

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
 */
class V2Test extends ATest
{
    /**
     * @return V2
     */
    public function test__construct(): V2
    {
        $cls = new V2(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection(), new \Bolt\helpers\ServerState());
        $this->assertInstanceOf(V2::class, $cls);
        return $cls;
    }
}
