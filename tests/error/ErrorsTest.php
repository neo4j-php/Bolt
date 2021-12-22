<?php

namespace Bolt\tests\error;

use Bolt\tests\ATest;

/**
 * Class ErrorsTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\error\ConnectException
 * @covers \Bolt\error\MessageException
 * @covers \Bolt\error\PackException
 * @covers \Bolt\error\UnpackException
 *
 * @package Bolt\tests
 * @requires PHP >= 7.1
 * @requires extension sockets
 * @requires extension mbstring
 */
class ErrorsTest extends ATest
{
    public function testConnectException()
    {
        $conn = new \Bolt\connection\StreamSocket('127.0.0.1', 7800, 1);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);
        $this->expectException(\Bolt\error\ConnectException::class);
        $conn->connect();
    }

    public function testMessageException()
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = null;
        try {
            $bolt = new \Bolt\Bolt($conn);
            $this->assertInstanceOf(\Bolt\Bolt::class, $bolt);

            $protocol = $bolt->build();
        } catch (\Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        $this->expectException(\Bolt\error\MessageException::class);
        $protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));
        $protocol->run('Wrong message');
    }

    public function testPackException1()
    {
        $packer = new \Bolt\PackStream\v1\Packer();
        $this->assertInstanceOf(\Bolt\PackStream\v1\Packer::class, $packer);
        $this->expectException(\Bolt\error\PackException::class);
        foreach ($packer->pack(0x00, fopen('php://input', 'r')) as $chunk) { }
    }

    public function testPackException2()
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = null;
        try {
            $bolt = new \Bolt\Bolt($conn);
            $this->assertInstanceOf(\Bolt\Bolt::class, $bolt);
        } catch (\Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }

        $this->expectException(\Bolt\error\PackException::class);
        $bolt->setPackStreamVersion(2);
    }
}
