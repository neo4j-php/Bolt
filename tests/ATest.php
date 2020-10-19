<?php

namespace Bolt\tests;

use \PHPUnit\Framework\TestCase;

/**
 * Class TestBase
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\tests
 */
abstract class ATest extends TestCase
{

    /**
     * @var bool
     */
    private static $bypass = false;

    /**
     * Bypass Socket final keyword before autoload
     */
    public static function setUpBeforeClass(): void
    {
        if (!self::$bypass) {
            $path = __DIR__;
            while (!file_exists($path . DS . 'Socket.php')) {
                $path = dirname($path);
            }

            $content = file_get_contents($path . DS . 'Socket.php');
            if (strpos($content, "final class Socket") !== false) {
                file_put_contents($path . DS . 'Socket.php', str_replace('final class Socket', 'class Socket', $content));
                $socket = new \Bolt\Socket('localhost', 0, 0);
                file_put_contents($path . DS . 'Socket.php', $content);
            }
        }

        self::$bypass = true;
    }

}
