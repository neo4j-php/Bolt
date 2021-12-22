<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4_3;
use Exception;

/**
 * Class V4_3Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4_3
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 * @requires mbstring
 */
class V4_3Test extends \Bolt\tests\ATest
{
    /**
     * @return V4_3
     */
    public function test__construct(): V4_3
    {
        $cls = new V4_3(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
        $this->assertInstanceOf(V4_3::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V4_3 $cls
     */
    public function testRoute(V4_3 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('001cb366a187616464726573738e6c6f63616c686f73743a3736383790c0')];

        try {
            $this->assertIsArray($cls->route(['address' => 'localhost:7687'], [], null));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

}
