<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 3
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-summary-3
 * @package Bolt\protocol
 */
class V3 extends AProtocol
{
    use \Bolt\protocol\v1\SetAvailableStructures;

    use \Bolt\protocol\v1\PullAllMessage;
    use \Bolt\protocol\v1\DiscardAllMessage;
    use \Bolt\protocol\v1\ResetMessage;

    use \Bolt\protocol\v3\HelloMessage;
    use \Bolt\protocol\v3\RunMessage;
    use \Bolt\protocol\v3\BeginMessage;
    use \Bolt\protocol\v3\CommitMessage;
    use \Bolt\protocol\v3\RollbackMessage;
    use \Bolt\protocol\v3\GoodbyeMessage;
}
