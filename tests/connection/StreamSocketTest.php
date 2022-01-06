<?php

namespace Bolt\tests\connection;

use Bolt\Bolt;
use Bolt\connection\StreamSocket;
use Bolt\error\ConnectException;
use Exception;
use PHPUnit\Framework\TestCase;

final class StreamSocketTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testMillisecondTimeout(): void
    {
        $socket = new StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int) ($GLOBALS['NEO_PORT'] ?? 7687), 1.5);
        $bolt = new Bolt($socket);
        $protocol = $bolt->build();
        $protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
  MERGE (d:Day {day: i})
)');
        } catch (ConnectException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.5, $newTime - $time, 0.2);
        }

        self::assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testSecondsTimeout(): void
    {
        $socket = new StreamSocket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', (int) ($GLOBALS['NEO_PORT'] ?? 7687), 1);
        $bolt = new Bolt($socket);
        $protocol = $bolt->build();
        $protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS']));

        $time = microtime(true);
        try {
            $protocol->run('FOREACH ( i IN range(1,10000) | 
  MERGE (d:Day {day: i})
)');
            $this->fail('No timeout error triggered');
        } catch (ConnectException $e) {
            $newTime = microtime(true);

            $this->assertEqualsWithDelta(1.0, $newTime - $time, 0.2);
        }
    }
}
