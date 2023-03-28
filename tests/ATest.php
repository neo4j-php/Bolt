<?php

namespace Bolt\tests;

use Bolt\protocol\{AProtocol, Response, V1, V2, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1};
use Bolt\helpers\Auth;

/**
 * Class ATest
 * @package Bolt\tests
 */
class ATest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $user = getenv('GDB_USERNAME');
        if (!empty($user))
            $GLOBALS['NEO_USER'] = $user;
        $pwd = getenv('GDB_PASSWORD');
        if (!empty($pwd))
            $GLOBALS['NEO_PASS'] = $pwd;
        $host = getenv('GDB_HOST');
        if (!empty($host))
            $GLOBALS['NEO_HOST'] = $host;
        $port = getenv('GDB_PORT');
        if (!empty($port))
            $GLOBALS['NEO_PORT'] = $port;
    }

    protected function sayHello(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol, string $name, string $password)
    {
        if (version_compare($protocol->getVersion(), '5.1', '<')) {
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello(Auth::basic($name, $password))->getSignature());
        } else {
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->hello()->getSignature());
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $protocol->logon([
                'scheme' => 'basic',
                'principal' => $name,
                'credentials' => $password
            ])->getSignature());
        }
    }

    protected function basicRun(AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol, string $query, array $params = []): array
    {
        $response = $protocol->run($query, $params)->getResponse();
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature(), 'No Success Response: ' . json_encode([
            'content' => $response->getContent(),
            'signature' => $response->getSignature()
        ]));

        if (method_exists($protocol, 'pullAll')) {
            $protocol->pullAll();
        } else {
            $protocol->pull();
        }
        $results = [];
        foreach ($protocol->getResponses() as $response) {
            $this->assertContains(
                $response->getSignature(),
                [Response::SIGNATURE_SUCCESS, Response::SIGNATURE_RECORD],
                'No Success Response: ' . json_encode([
                    'content' => $response->getContent(),
                    'signature' => $response->getSignature()
                ])
            );
            if ($response->getSignature() === Response::SIGNATURE_RECORD) {
                $results[] = $response->getContent();
            }
        }
        return $results;
    }
}
