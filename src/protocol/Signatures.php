<?php

namespace Bolt\protocol;

class Signatures
{
    public const INIT = 0x01;
    public const HELLO = 0x01;
    public const GOODBYE = 0x02;
    public const ACK_FAILURE = 0x0E;
    public const RESET = 0x0F;

    public const RUN = 0x10;
    public const BEGIN = 0x11;
    public const COMMIT = 0x12;
    public const ROLLBACK = 0x13;

    public const DISCARD = 0x2F;
    public const DISCARD_ALL = 0x2F;

    public const PULL_ALL = 0x3F;
    public const PULL = 0x3F;

    public const ROUTE = 0x66;

    public const SUCCESS = 0x70;
    public const RECORD = 0x71;
    public const IGNORED = 0x7E;
    public const FAILURE = 0x7F;
}