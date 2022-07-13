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
class V4Test extends ATest
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
        self::$writeBuffer = [
            hex2bin('0001b1'),
            hex2bin('00013f'),
            hex2bin('0001a2'),
            hex2bin('0002816e'),
            hex2bin('0001ff'),
            hex2bin('000483716964'),
            hex2bin('0001ff'),
        ];

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
            hex2bin('0001b1'),
            hex2bin('00013f'),
            hex2bin('0001a2'),
            hex2bin('0002816e'),
            hex2bin('0001ff'),
            hex2bin('000483716964'),
            hex2bin('0001ff'),
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
        self::$writeBuffer = [
            hex2bin('0001b1'),
            hex2bin('00012f'),
            hex2bin('0001a2'),
            hex2bin('0002816e'),
            hex2bin('0001ff'),
            hex2bin('000483716964'),
            hex2bin('0001ff'),
        ];

        try {
            $this->assertIsArray($cls->discard(['n' => -1, 'qid' => -1]));
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

}
