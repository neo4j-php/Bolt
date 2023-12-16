<?php

namespace Bolt\enum;

/**
 * Enum Message
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\enum
 */
enum Message
{
    case INIT;
    case HELLO;
    case RESET;
    case RUN;
    case PULL;
    case PULL_ALL;
    case DISCARD;
    case DISCARD_ALL;
    case BEGIN;
    case COMMIT;
    case ROLLBACK;
    case ROUTE;
    case ACK_FAILURE;
    case LOGON;
    case LOGOFF;
    case TELEMETRY;
}
