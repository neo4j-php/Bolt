<?php

namespace Bolt\enum;

/**
 * Enum Message
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\enum
 */
enum Message: string
{
    case INIT = 'INIT';
    case HELLO = 'HELLO';
    case RESET = 'RESET';
    case RUN = 'RUN';
    case PULL = 'PULL';
    case PULL_ALL = 'PULL_ALL';
    case DISCARD = 'DISCARD';
    case DISCARD_ALL = 'DISCARD_ALL';
    case BEGIN = 'BEGIN';
    case COMMIT = 'COMMIT';
    case ROLLBACK = 'ROLLBACK';
    case ROUTE = 'ROUTE';
    case ACK_FAILURE = 'ACK_FAILURE';
    case LOGON = 'LOGON';
    case LOGOFF = 'LOGOFF';
    case TELEMETRY = 'TELEMETRY';
}
