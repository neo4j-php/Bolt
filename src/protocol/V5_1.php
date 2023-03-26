<?php

namespace Bolt\protocol;

/**
 * Class Protocol version 5.1
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/message/
 * @package Bolt\protocol
 */
class V5_1 extends AProtocol
{
    use \Bolt\protocol\v5\AvailableStructures;

    use \Bolt\protocol\v1\ResetMessage;

    use \Bolt\protocol\v3\RunMessage;
    use \Bolt\protocol\v3\BeginMessage;
    use \Bolt\protocol\v3\CommitMessage;
    use \Bolt\protocol\v3\RollbackMessage;
    use \Bolt\protocol\v3\GoodbyeMessage;

    use \Bolt\protocol\v4\PullMessage;
    use \Bolt\protocol\v4\DiscardMessage;

    use \Bolt\protocol\v4_4\RouteMessage;

    use \Bolt\protocol\v5_1\HelloMessage;
    use \Bolt\protocol\v5_1\LogonMessage;
    use \Bolt\protocol\v5_1\LogoffMessage;
}