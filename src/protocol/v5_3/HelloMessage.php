<?php

namespace Bolt\protocol\v5_3;

use Bolt\protocol\Response;
use Bolt\error\BoltException;

trait HelloMessage
{
    use \Bolt\protocol\v5_1\HelloMessage {
        \Bolt\protocol\v5_1\HelloMessage::hello as __hello;
    }

    /**
     * Send HELLO message
     * The HELLO message request the connection to be authorized for use with the remote database.
     *
     * @link https://www.neo4j.com/docs/bolt/current/bolt/message/#messages-hello
     * @param array $extra Use \Bolt\helpers\Auth to generate appropiate array
     * @throws BoltException
     */
    public function hello(array $extra = []): Response
    {
        $extra['bolt_agent'] = array_merge([
            'product' => 'php-bolt/' . \Composer\InstalledVersions::getPrettyVersion('stefanak-michal/bolt'),
            'platform' => php_uname(),
            'language' => 'PHP/' . phpversion(),
            'language_details' => 'null'
        ], $extra['bolt_agent'] ?? []);

        return $this->__hello($extra);
    }
}
