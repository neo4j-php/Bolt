<?php

namespace Bolt\tests\error;

use Bolt\protocol\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class ErrorsTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\error\ConnectException
 * @covers \Bolt\error\PackException
 * @covers \Bolt\error\UnpackException
 *
 * @package Bolt\tests
 */
class ErrorsTest extends TestCase
{
    public function testConnectException()
    {
        $conn = new \Bolt\connection\StreamSocket('127.0.0.1', 7800, 1);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);
        $this->expectException(\Bolt\error\ConnectException::class);
        $conn->connect();
    }

    public function testPackException1()
    {
        $packer = new \Bolt\packstream\v1\Packer();
        $this->assertInstanceOf(\Bolt\packstream\v1\Packer::class, $packer);
        $this->expectException(\Bolt\error\PackException::class);
        foreach ($packer->pack(0x00, fopen('php://input', 'r')) as $chunk) {
            //expecting exception
        }
    }

    public function testPackException2()
    {
        $conn = new \Bolt\connection\StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687);
        $this->assertInstanceOf(\Bolt\connection\StreamSocket::class, $conn);

        $bolt = null;
        $bolt = new \Bolt\Bolt($conn);
        $this->assertInstanceOf(\Bolt\Bolt::class, $bolt);

        $this->expectException(\Bolt\error\PackException::class);
        $bolt->setPackStreamVersion(2);
    }
}
