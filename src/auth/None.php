<?php

namespace Bolt\auth;

/**
 * Class None
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\auth
 */
class None extends Auth
{
    /**
     * @inheritdoc
     */
    protected $scheme = 'none';

    /**
     * @inheritDoc
     */
    public function getCredentials(): array
    {
        return [
            'user_agent' => $this->userAgent,
            'scheme' => $this->scheme
        ];
    }
}
