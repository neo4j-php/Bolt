<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_4;

/**
 * Class V4_4Test
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_4
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 * @requires mbstring
 */
class V4_4Test extends \Bolt\tests\ATest
{
    /**
     * @return V4_4
     */
    public function test__construct()
    {
        $cls = new V4_4(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
        $this->assertInstanceOf(V4_4::class, $cls);
        return $cls;
    }

}
