<?php

namespace Bolt\auth;

/**
 * Class Basic
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\auth
 */
class Basic extends Auth
{
    /**
     * @inheritdoc
     */
    protected $scheme = 'basic';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $username
     * @param string $password
     */
    public function setCredentials(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(): array
    {
        return [
            'user_agent' => $this->userAgent,
            'scheme' => $this->scheme,
            'principal' => $this->username,
            'credentials' => $this->password
        ];
    }
}
