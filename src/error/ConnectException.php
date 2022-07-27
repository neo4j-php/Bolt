<?php

namespace Bolt\error;

use Bolt\helpers\ServerState;
use Exception;
use Throwable;

/**
 * Class ConnectException
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\error
 */
class ConnectException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        ServerState::set(ServerState::DEFUNCT);
    }
}
