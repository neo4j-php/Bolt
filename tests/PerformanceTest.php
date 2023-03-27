<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\protocol\{AProtocol, Response, V4_4, V5, V5_1};
use Bolt\tests\packstream\v1\generators\RandomDataGenerator;

/**
 * Class PerformanceTest
 * @author Ghlen Nagels
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class PerformanceTest extends ATest
{
    use CreatesSockets;

    public function test50KRecords(): void
    {
        $amount = 1;

        $conn = $this->createStreamSocket();

        /** @var AProtocol|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->setProtocolVersions(5.1, 5, 4.4)->build();

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);
        $response = $protocol->run('MATCH (n) DELETE n')->getResponse();
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());
        $response = iterator_to_array($protocol->pull()->getResponses());

        //prevent multiple runs at once
        while (true) {
            $response = $protocol->run('MATCH (n:Test50k) RETURN count(n)')->getResponse();
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());

            $protocol->pull();
            $response = $protocol->getResponse();
            $this->assertEquals(Response::SIGNATURE_RECORD, $response->getSignature());
            $runs = $response->getContent()[0];

            $response = $protocol->getResponse();
            $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());

            if ($runs > 0) {
                sleep(60);
            } else {
                $response = $protocol->run('CREATE (n:Test50k)')->getResponse();
                $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());
                $response = $protocol->pull()->getResponse();
                $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());
                break;
            }
        }

        $generator = new RandomDataGenerator($amount);
        $response = $protocol
            ->run('UNWIND $x as x RETURN x', ['x' => $generator])
            ->getResponse();
        $this->assertEquals(Response::SIGNATURE_SUCCESS, $response->getSignature());

        $iterator = $protocol->pull()->getResponses();
        $count = 0;
        /** @var Response $response */
        foreach ($iterator as $response) {
            if ($response->getSignature() === Response::SIGNATURE_RECORD)
                $count++;
        }

        $protocol->run('MATCH (n:Test50k) DELETE n')->getResponse();
        $protocol->pull()->getResponse();
        $this->assertEquals($amount, $count);
    }
}
