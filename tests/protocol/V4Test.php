<?php

namespace Bolt\tests\protocol;

use Bolt\protocol\V4;
use Exception;

/**
 * Class V4Test
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\protocol\AProtocol
 * @covers \Bolt\protocol\V4
 *
 * @package Bolt\tests\protocol
 * @requires PHP >= 7.1
 * @requires mbstring
 */
class V4Test extends \Bolt\tests\ATest
{
    /**
     * @return V4
     */
    public function test__construct(): V4
    {
        $cls = new V4(new \Bolt\PackStream\v1\Packer, new \Bolt\PackStream\v1\Unpacker, $this->mockConnection());
        $this->assertInstanceOf(V4::class, $cls);
        return $cls;
    }

    /**
     * @depends test__construct
     * @param V4 $cls
     */
    public function testPull(V4 $cls)
    {
        self::$readArray = [1, 3, 0, 1, 2, 0];
        self::$writeBuffer = [hex2bin('000bb13fa2816eff83716964ff')];

        try {
            $res = $cls->pull(['n' => -1, 'qid' => -1]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        $this->assertIsArray($res);
        $this->assertCount(2, $res);
    }

    /**
     * @depends test__construct
     * @param V4 $cls
     */
    public function testPullFail(V4 $cls)
    {
        self::$readArray = [4, 5, 0, 1, 2, 0];
        self::$writeBuffer = [
            hex2bin('000bb13fa2816eff83716964ff'),
            hex2bin('0002b00f')
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('some error message (Neo.ClientError.Statement.SyntaxError)');
        $cls->pull(['n' => -1, 'qid' => -1]);
    }

    /**
     * @depends test__construct
     * @param V4 $cls
     */
    public function testDiscard(V4 $cls)
    {
        self::$readArray = [1, 2, 0];
        self::$writeBuffer = [hex2bin('000bb12fa2816eff83716964ff')];

        try {
            $this->assertIsArray($cls->discard(['n' => -1, 'qid' => -1]));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

}
