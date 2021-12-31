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
    /**
     * @var string
     */
    private $serverMessage;

    /**
     * @var string
     */
    private $serverCode;

    /**
     * @param string $message
     * @param string $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, string $code, ?Throwable $previous = null)
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
     * @return string
     */
    public function getServerMessage(): string
    {
        return $this->serverMessage;
    }

}
