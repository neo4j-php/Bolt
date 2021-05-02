<?php

namespace Bolt\tests;

/**
 * Class ErrorsTest
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
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
        $this->expectException(\Bolt\error\ConnectException::class);
        ini_set('default_socket_timeout', 1000);
        $conn = new \Bolt\connection\StreamSocket('1.1.1.1', 7687, 1);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);
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
        } catch (\Exception $e) {
            $this->markTestIncomplete($e->getMessage());
            return;
        }

        $this->expectException(\Bolt\error\MessageException::class);
        $bolt->hello('Test/1.0', $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $bolt->run('Wrong message');
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
            return;
        }

        $this->expectException(\Bolt\error\PackException::class);
        $bolt->setPackStreamVersion(2);
    }
}
