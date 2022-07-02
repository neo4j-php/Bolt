<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://7687.org/bolt/bolt-protocol-message-specification-1.html
 * @package Bolt\protocol
 */
class V1 extends AProtocol
{
    use \Bolt\protocol\v1\InitMessage;
    use \Bolt\protocol\v1\RunMessage;
    use \Bolt\protocol\v1\PullAllMessage;
    use \Bolt\protocol\v1\DiscardAllMessage;
    use \Bolt\protocol\v1\ResetMessage;
    use \Bolt\protocol\v1\AckFailureMessage;
}
