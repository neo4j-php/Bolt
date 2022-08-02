<?php

namespace Bolt\tests\protocol;

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
     * @var string Temporal buffer for packed message to be read
     */
    private static string $readBuffer = '';
    /**
     * @var array Order of consecutive returns from "read" method calls
     */
    protected static array $readArray = [];
    /**
     * @var int Internal pointer for "writeBuffer"
     */
    private static int $writeIndex = 0;
    /**
     * @var array Expected write buffers or keep empty to skip verification
     */
    protected static array $writeBuffer = [];

    private static \Bolt\PackStream\v1\Packer $packer;

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
     * @param int $length
     * @return string
     */
    public function readCallback(int $length = 2048): string
    {
        if (empty(self::$readBuffer)) {
            $params = array_shift(self::$readArray);
            $gen = self::$packer->pack(...$params);
            foreach ($gen as $s) {
                self::$readBuffer .= mb_strcut($s, 2, null, '8bit');
            }

            self::$readBuffer = pack('n', mb_strlen(self::$readBuffer, '8bit')) . self::$readBuffer . chr(0x00) . chr(0x00);
        }

        $output = mb_strcut(self::$readBuffer, 0, $length, '8bit');
        self::$readBuffer = mb_strcut(self::$readBuffer, mb_strlen($output, '8bit'), null, '8bit');
        return $output;
    }

    /**
     * Reset mockup AConnetion variables
     */
    protected function setUp(): void
    {
        self::$readBuffer = '';
        self::$readArray = [];
        self::$writeIndex = 0;
        self::$writeBuffer = [];

        self::$packer = new \Bolt\PackStream\v1\Packer();
    }
}
