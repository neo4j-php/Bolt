<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 2
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\protocol
 */
class V2 extends AProtocol
{
    use \Bolt\protocol\v1\SetAvailableStructures;

    use \Bolt\protocol\v1\InitMessage;
    use \Bolt\protocol\v1\RunMessage;
    use \Bolt\protocol\v1\PullAllMessage;
    use \Bolt\protocol\v1\DiscardAllMessage;
    use \Bolt\protocol\v1\ResetMessage;
    use \Bolt\protocol\v1\AckFailureMessage;
}
