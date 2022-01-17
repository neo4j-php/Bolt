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
    public static $defaultUserAgent = 'bolt-php';

    /**
     * None authorization
     * @param string|null $userAgent
     * @return array
     */
    public static function none(?string $userAgent = null): array
    {
        return [
            'user_agent' => $userAgent ?? self::$defaultUserAgent,
            'scheme' => 'none'
        ];
    }

    /**
     * Basic authorization with username and password
     * @param string $username
     * @param string $password
     * @param string|null $userAgent
     * @return array
     */
    public static function basic(string $username, string $password, ?string $userAgent = null): array
    {
        return [
            'user_agent' => $userAgent ?? self::$defaultUserAgent,
            'scheme' => 'basic',
            'principal' => $username,
            'credentials' => $password
        ];
    }

    /**
     * OIDC authorization with token
     * @param string $token
     * @param string|null $userAgent
     * @return array
     */
    public static function bearer(string $token, ?string $userAgent = null): array
    {
        return [
            'user_agent' => $userAgent ?? self::$defaultUserAgent,
            'scheme' => 'bearer',
            'credentials' => $token
        ];
    }

    /**
     * Kerberos authorization with token
     * @param string $token
     * @param string|null $userAgent
     * @return array
     */
    public static function kerberos(string $token, ?string $userAgent = null): array
    {
        return [
            'user_agent' => $userAgent ?? self::$defaultUserAgent,
            'scheme' => 'kerberos',
            'principal' => '',
            'credentials' => $token
        ];
    }
}
