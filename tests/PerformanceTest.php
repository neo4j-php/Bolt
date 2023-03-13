<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\Socket;
use Bolt\protocol\{AProtocol, Response, V1, V2, V3, V4, V4_1, V4_2, V4_3, V4_4, V5, V5_1};
use Bolt\tests\packstream\v1\generators\RandomDataGenerator;

/**
 * Class PerformanceTest
 * @author Ghlen Nagels
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class PerformanceTest extends ATest
{
    public function test50KRecords(): void
    {
        $amount = 50000;

        $conn = new Socket($GLOBALS['NEO_HOST'] ?? 'localhost', $GLOBALS['NEO_PORT'] ?? 7687, 60);
        /** @var AProtocol|V1|V2|V3|V4|V4_1|V4_2|V4_3|V4_4|V5|V5_1 $protocol */
        $protocol = (new Bolt($conn))->build();

        $this->sayHello($protocol, $GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']);

        //prevent multiple runs at once
        while (true) {
            $protocol->run('MATCH (n:Test50k) RETURN count(n)')->getResponse();
            $response = $protocol->pull()->getResponse();
            if ($response !== Response::SIGNATURE_RECORD)
                $this->markTestSkipped();
            $runs = $response->getContent()[0];
            $protocol->getResponse();
            if ($runs > 0) {
                sleep(60);
            } else {
                $protocol->run('CREATE (n:Test50k)')->getResponse();
                break;
            }
        }

        $generator = new RandomDataGenerator($amount);
        $protocol
            ->run('UNWIND $x as x RETURN x', ['x' => $generator])
            ->getResponse();


        $iterator = $protocol->pull()->getResponses();
        $count = 0;
        /** @var Response $response */
        foreach ($iterator as $response) {
            if ($response->getSignature() === Response::SIGNATURE_RECORD)
                $count++;
        }

        $protocol->run('MATCH (n:Test50k) DELETE n');
        $this->assertEquals($amount, $count);
    }
}
