<?php

namespace Bolt\protocol;

use Bolt\enum\{Message, Signature};

/**
 * Class Response
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
class Response
{
    public function __construct(
        private readonly Message   $message,
        private readonly Signature $signature,
        private readonly array     $content = []
    )
    {
    }

    /**
     * Get requested bolt message name
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Signature tells you result of your request
     */
    public function getSignature(): Signature
    {
        return $this->signature;
    }

    /**
     * Get response content
     */
    public function getContent(): array
    {
        return $this->content;
    }
}
