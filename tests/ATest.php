<?php

namespace Bolt\tests;

use PHPUnit\Framework\TestCase;
use Bolt\connection\AConnection;

/**
 * Class ATest
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
abstract class ATest extends TestCase
{

    /**
     * @var int Internal pointer for "readArray"
     */
    static $readIndex = 0;
    /**
     * @var array Order of consecutive returns from "read" method calls
     */
    static $readArray = [];
    /**
     * @var int Internal pointer for "writeBuffer"
     */
    static $writeIndex = 0;
    /**
     * @var array Expected write buffers or keep empty to skip verification
     */
    static $writeBuffer = [];

    /**
     * Mock Socket class with "write" and "read" methods
     * @return AConnection
     */
    protected function mockConnection(): AConnection
    {
        $mockBuilder = $this
            ->getMockBuilder(AConnection::class)
            ->disableOriginalConstructor();
        call_user_func([$mockBuilder, method_exists($mockBuilder, 'onlyMethods') ? 'onlyMethods' : 'setMethods'], ['__construct', 'write', 'read', 'connect', 'disconnect']);
        $connection = $mockBuilder->getMock();

        $connection
            ->method('write')
            ->with(
                $this->callback(function ($buffer) {
                    var_dump(bin2hex($buffer));
                    if (bin2hex($buffer) == '0000')
                        return true;

                    $i = self::$writeIndex;
                    self::$writeIndex++;

                    //skip write buffer check
                    if (empty(self::$writeBuffer))
                        return true;

                    //verify expected buffer
                    return (self::$writeBuffer[$i] ?? '') === $buffer;
                })
            );

        $connection
            ->method('read')
            ->will($this->returnCallback([$this, 'readCallback']));

        /** @var AConnection $connection */
        return $connection;
    }

    /**
     * Mocked Socket read method
     * @return string
     */
    public function readCallback(): string
    {
        switch (self::$readArray[self::$readIndex]) {
            case 1:
                $output = hex2bin('0003'); // header of length 3
                break;
            case 2:
                $output = hex2bin('B170A0'); // success {}
                break;
            case 3:
                $output = hex2bin('B171A0'); // record {}
                break;
            case 4:
                $output = hex2bin('004b'); // failure header
                break;
            case 5:
                $output = hex2bin('b17fa284636f6465d0254e656f2e436c69656e744572726f722e53746174656d656e742e53796e7461784572726f72876d657373616765d012736f6d65206572726f72206d657373616765'); // failure message
                break;
            default:
                $output = hex2bin('0000'); // end
        }

        self::$readIndex++;
        return (string)$output;
    }

    /**
     * Reset mockup AConnetion variables
     */
    protected function setUp(): void
    {
        self::$readIndex = 0;
        self::$readArray = [];
        self::$writeIndex = 0;
        self::$writeBuffer = [];
    }
}
