<?php

namespace Bolt\helpers;

/**
 * Class Auth
 * Helper to generate array of extra parameters for INIT/HELLO message
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\helpers
 */
class Auth
{
    /**
     * @var string
     */
    public static $userAgent = 'bolt-php';

    /**
     * None authorization
     * @return array
     */
    public static function none(): array
    {
        return [
            'user_agent' => self::$userAgent,
            'scheme' => 'none'
        ];
    }

    /**
     * Basic authorization with username and password
     * @param string $username
     * @param string $password
     * @return array
     */
    public static function basic(string $username, string $password): array
    {
        return [
            'user_agent' => self::$userAgent,
            'scheme' => 'basic',
            'principal' => $username,
            'credentials' => $password
        ];
    }

    /**
     * OIDC authorization with token
     * @param string $token
     * @return array
     */
    public static function bearer(string $token): array
    {
        return [
            'user_agent' => self::$userAgent,
            'scheme' => 'bearer',
            'credentials' => $token
        ];
    }
}
