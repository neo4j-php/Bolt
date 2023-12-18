<?php

namespace Bolt\enum;

/**
 * Enum Signature
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\enum
 */
enum Signature: int
{
    case NONE = 0;
    case SUCCESS = 0x70; //112
    case FAILURE = 0x7F; //127
    case IGNORED = 0x7E; //126
    case RECORD = 0x71; //113
}
