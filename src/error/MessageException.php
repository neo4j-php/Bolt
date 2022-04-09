<?php

namespace Bolt\error;

use Exception;
use Throwable;

/**
 * Class MessageException
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\error
 */
class MessageException extends Exception
{
    private ?string $serverMessage;
    private string $serverCode;

    /**
     * @param string|null $message
     * @param string $code
     * @param Throwable|null $previous
     */
    public function __construct(?string $message, string $code, ?Throwable $previous = null)
    {
        $this->serverCode = $code;
        $this->serverMessage = $message;
        parent::__construct($message . ' (' . $code . ')', 0, $previous);
    }

    /**
     * @return string
     */
    public function getServerCode(): string
    {
        return $this->serverCode;
    }

    /**
     * @return string|null
     */
    public function getServerMessage(): ?string
    {
        return $this->serverMessage;
    }

}
