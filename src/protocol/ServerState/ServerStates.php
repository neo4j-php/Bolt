<?php

namespace Bolt\protocol\ServerState;

use Bolt\protocol\MainBoltVersions;

class ServerStates
{
    public const CONNECTED = 0;
    public const DEFUNCT = 1;
    public const DISCONNECTED = 2;
    public const FAILED = 3;
    public const INTERRUPTED = 4;
    public const READY = 5;
    public const STREAMING = 6;
    public const TX_READY = 7;
    public const TX_STREAMING = 8;

    public const UNKNOWN = -1;
}