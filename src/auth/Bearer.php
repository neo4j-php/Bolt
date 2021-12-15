<?php

namespace Bolt\auth;

/**
 * Class Bearer
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\auth
 */
class Bearer extends Auth
{
    /**
     * @inheritdoc
     */
    protected $scheme = 'bearer';

    /**
     * @var string
     */
    private $token;

    /**
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(): array
    {
        return [
            'user_agent' => $this->userAgent,
            'scheme' => $this->scheme,
            'credentials' => $this->token
        ];
    }
}
