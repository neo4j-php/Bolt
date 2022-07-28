<?php

namespace Bolt\error;

use Exception;
use Throwable;

/**
 * Class IgnoreException
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\error
 */
class IgnoredException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(strtoupper($message) . ' message IGNORED. Server in FAILED or INTERRUPTED state.', $code, $previous);
    }
}
