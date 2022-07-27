<?php

namespace Bolt\error;

use Exception;
use Bolt\helpers\ServerState;

/**
 * Class ServerStateException
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\error
 */
class ServerStateException extends Exception
{
    /**
     * Create new instance of ServerStateException with predefined error message
     * @param string ...$states
     * @return ServerStateException
     */
    public static function expected(string ...$states): ServerStateException
    {
        return new self('Server in ' . ServerState::get() . ' state. Expected ' . implode(' or ', $states) . '.');
    }
}
