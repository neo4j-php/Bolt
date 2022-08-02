<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\connection\StreamSocket;
use Bolt\helpers\Auth;
use Bolt\tests\PackStream\v1\generators\RandomDataGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Class PerformanceTest
 * @author Ghlen Nagels
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\tests
 */
class PerformanceTest extends TestCase
{
    public function test50KRecords(): void
    {
        $amount = 50000;

        try {
            $conn = new StreamSocket($GLOBALS['NEO_HOST'] ?? 'localhost', $GLOBALS['NEO_PORT'] ?? 7687, 60);
            /** @var \Bolt\protocol\V4_3|\Bolt\protocol\V4_4 $protocol */
            $protocol = (new Bolt($conn))->build();
            $this->assertEquals(\Bolt\protocol\Response::SIGNATURE_SUCCESS, $protocol->hello(Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']))->getSignature());

            $generator = new RandomDataGenerator($amount);
            $protocol->run('UNWIND $x as x RETURN x', ['x' => $generator])->getResponse();

            $count = 0;
            while (true) {
                $response = $protocol->pull(['n' => 1])->getResponse();
                $this->assertEquals(\Bolt\protocol\Response::SIGNATURE_RECORD, $response->getSignature());
                $meta = $protocol->getResponse();
                $this->assertEquals(\Bolt\protocol\Response::SIGNATURE_SUCCESS, $meta->getSignature());

                $count++;

                if ($meta->getContent()['has_more'] ?? false)
                    continue;
                else
                    break;
            }

            $this->assertEquals($amount, $count);
        } catch (\Exception $e) {
            var_dump($e->getTraceAsString());
            $this->markTestIncomplete($e->getMessage());
        }
    }
}
