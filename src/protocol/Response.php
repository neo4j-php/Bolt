<?php

namespace Bolt\protocol;

/**
 * Class Response
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
class Response
{
    public const MESSAGE_INIT = 'INIT';
    public const MESSAGE_HELLO = 'HELLO';
    public const MESSAGE_RESET = 'RESET';
    public const MESSAGE_RUN = 'RUN';
    public const MESSAGE_PULL = 'PULL';
    public const MESSAGE_PULL_ALL = 'PULL_ALL';
    public const MESSAGE_DISCARD = 'DISCARD';
    public const MESSAGE_DISCARD_ALL = 'DISCARD_ALL';
    public const MESSAGE_BEGIN = 'BEGIN';
    public const MESSAGE_COMMIT = 'COMMIT';
    public const MESSAGE_ROLLBACK = 'ROLLBACK';
    public const MESSAGE_ROUTE = 'ROUTE';
    public const MESSAGE_ACK_FAILURE = 'ACK_FAILURE';
    public const MESSAGE_LOGON = 'LOGON';
    public const MESSAGE_LOGOFF = 'LOGOFF';
    public const MESSAGE_TELEMETRY = 'TELEMETRY';

    public const SIGNATURE_SUCCESS = 0x70; //112
    public const SIGNATURE_FAILURE = 0x7F; //127
    public const SIGNATURE_IGNORED = 0x7E; //126
    public const SIGNATURE_RECORD = 0x71; //113

    public function __construct(
        private string $message,
        private int    $signature,
        private array  $content = []
    )
    {
    }

    /**
     * Get requested bolt message name
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Signature tells you result of your request
     */
    public function getSignature(): int
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
