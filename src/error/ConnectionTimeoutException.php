<?php

namespace Bolt\error;

class ConnectionTimeoutException extends ConnectException
{
    public static function createFromTimeout(float $timeout): self
    {
        return new self('Connection timeout reached after '.$timeout.' seconds.');
    }
}