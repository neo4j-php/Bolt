<?php

namespace Bolt\tests;

/**
 * Class ATest
 * @package Bolt\tests
 */
class ATest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $user = getenv('GDB_USERNAME');
        if (!empty($user))
            $GLOBALS['NEO_USER'] = $user;
        $pwd = getenv('GDB_PASSWORD');
        if (!empty($pwd))
            $GLOBALS['NEO_PASS'] = $pwd;
        $host = getenv('GDB_HOST');
        if (!empty($host))
            $GLOBALS['NEO_HOST'] = $host;
        $port = getenv('GDB_PORT');
        if (!empty($port))
            $GLOBALS['NEO_PORT'] = $port;
    }
}
