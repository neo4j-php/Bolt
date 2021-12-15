<?php

namespace Bolt\auth;

/**
 * Class Auth
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\auth
 */
abstract class Auth
{
    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @param string $userAgent
     */
    public function __construct(string $userAgent = 'bolt-php')
    {
        $this->userAgent = $userAgent;
    }

    /**
     * Returns structured array for init/hello message
     * @return array
     */
    abstract public function getCredentials(): array;
}
