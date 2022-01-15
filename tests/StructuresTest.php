<?php

namespace Bolt\tests;

use Bolt\Bolt;
use Bolt\protocol\AProtocol;
use Bolt\structures\{
    Node,
    Relationship,
    UnboundRelationship,
    Path,
    Date,
    Time,
    LocalTime,
    DateTime,
    DateTimeZoneId,
    LocalDateTime,
    Duration,
    Point2D,
    Point3D
};
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * Class StructuresTest
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 *
 * @covers \Bolt\structures\Date
 * @covers \Bolt\structures\DateTime
 * @covers \Bolt\structures\DateTimeZoneId
 * @covers \Bolt\structures\Duration
 * @covers \Bolt\structures\LocalDateTime
 * @covers \Bolt\structures\LocalTime
 * @covers \Bolt\structures\Node
 * @covers \Bolt\structures\Path
 * @covers \Bolt\structures\Point2D
 * @covers \Bolt\structures\Point3D
 * @covers \Bolt\structures\Relationship
 * @covers \Bolt\structures\Time
 * @covers \Bolt\structures\UnboundRelationship
 *
 * @covers \Bolt\PackStream\v1\Packer
 * @covers \Bolt\PackStream\v1\Unpacker
 *
 * @package Bolt\tests
 * @requires PHP >= 7.1
 */
class StructuresTest extends TestCase
{
    /**
     * How many iterations do for each date/time test
     * @var int
     */
    public static $iterations = 50;

    public function testInit(): AProtocol
    {
        try {
            $conn = new \Bolt\connection\Socket($GLOBALS['NEO_HOST'] ?? '127.0.0.1', $GLOBALS['NEO_PORT'] ?? 7687, 3);
            $this->assertInstanceOf(\Bolt\connection\Socket::class, $conn);

            $bolt = new Bolt($conn);
            $this->assertInstanceOf(Bolt::class, $bolt);

            $protocol = $bolt->build();
            $this->assertInstanceOf(AProtocol::class, $protocol);

            $this->assertNotEmpty($protocol->init(\Bolt\helpers\Auth::basic($GLOBALS['NEO_USER'], $GLOBALS['NEO_PASS'])));

            return $protocol;
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     */
    public function testDate(int $timestamp, AProtocol $protocol)
    {
        try {
            $date = gmdate('Y-m-d', $timestamp);

            //unpack
            $protocol->run('RETURN date($date)', [
                'date' => $date
            ]);
            $rows = $protocol->pull();
            $this->assertInstanceOf(Date::class, $rows[0][0]);
            $this->assertEquals($date, (string)$rows[0][0], 'unpack ' . $date . ' != ' . $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($date)', [
                'date' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            $this->assertEquals($date, $rows[0][0], 'pack ' . $date . ' != ' . $rows[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestampTimezone
     */
    public function testDateTime(int $timestamp, string $timezone, AProtocol $protocol)
    {
        try {
            $timestamp .= '.' . rand(0, 9e5);
            $datetime = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
                ->format('Y-m-d\TH:i:s.uP');

            //unpack
            $protocol->run('RETURN datetime($date)', [
                'date' => $datetime
            ]);
            $rows = $protocol->pull();
            $this->assertInstanceOf(DateTime::class, $rows[0][0]);
            $this->assertEquals($datetime, (string)$rows[0][0], 'unpack ' . $datetime . ' != ' . $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($date)', [
                'date' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            // neo4j returns fraction of seconds not padded with zeros ... zero timezone offset returns as Z
            $datetime = preg_replace(["/\.?0+(.\d{2}:\d{2})$/", "/\+00:00$/"], ['$1', 'Z'], $datetime);
            $this->assertEquals($datetime, $rows[0][0], 'pack ' . $datetime . ' != ' . $rows[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestampTimezone
     */
    public function testDateTimeZoneId(int $timestamp, string $timezone, AProtocol $protocol)
    {
        try {
            $timestamp .= '.' . rand(0, 9e5);
            $datetime = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
                    ->format('Y-m-d\TH:i:s.u') . '[' . $timezone . ']';

            //unpack
            $protocol->run('RETURN datetime($dt)', [
                'dt' => $datetime
            ]);
            $rows = $protocol->pull();
            $this->assertInstanceOf(DateTimeZoneId::class, $rows[0][0]);
            $this->assertEquals($datetime, (string)$rows[0][0], 'unpack ' . $datetime . ' != ' . $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($dt)', [
                'dt' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            // neo4j returns fraction of seconds not padded with zeros ... also contains timezone offset before timezone id
            $datetime = preg_replace("/\.?0+\[/", '[', $datetime);
            $rows[0][0] = preg_replace("/([+\-]\d{2}:\d{2}|Z)\[/", '[', $rows[0][0]);
            $this->assertEquals($datetime, $rows[0][0], 'pack ' . $datetime . ' != ' . $rows[0][0]);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Invalid value for TimeZone: Text \'' . $timezone . '\'') === 0) {
                $protocol->reset();
                $this->markTestSkipped('Test skipped because Neo4j missing timezone ID ' . $timezone);
            } else {
                $this->markTestIncomplete($e->getMessage());
            }
        }
    }

    /**
     * @depends testInit
     */
    public function testDuration(AProtocol $protocol)
    {
        try {
            foreach ([
                         'P1Y',
                         'P1M',
                         'P1D',
                         'PT1H',
                         'PT1M',
                         'PT1S',
                         'P1Y2M14DT16H12M35.765S'
                     ] as $duration) {
                //unpack
                $protocol->run('RETURN duration($d)', ['d' => $duration]);
                $rows = $protocol->pull();
                $this->assertInstanceOf(Duration::class, $rows[0][0]);
                $this->assertEquals($duration, (string)$rows[0][0], 'unpack ' . $duration . ' != ' . $rows[0][0]);

                //pack
                $protocol->run('RETURN toString($d)', [
                    'd' => $rows[0][0]
                ]);
                $rows = $protocol->pull();
                $this->assertEquals($duration, $rows[0][0], 'pack ' . $duration . ' != ' . $rows[0][0]);
            }
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     */
    public function testLocalDateTime(int $timestamp, AProtocol $protocol)
    {
        try {
            $timestamp .= '.' . rand(0, 9e5);
            $datetime = \DateTime::createFromFormat('U.u', $timestamp)
                ->format('Y-m-d\TH:i:s.u');

            //unpack
            $protocol->run('RETURN localdatetime($dt)', [
                'dt' => $datetime
            ]);
            $rows = $protocol->pull();
            $this->assertInstanceOf(LocalDateTime::class, $rows[0][0]);
            $this->assertEquals($datetime, (string)$rows[0][0], 'unpack ' . $datetime . ' != ' . $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($dt)', [
                'dt' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            $datetime = rtrim($datetime, '.0');
            $this->assertEquals($datetime, $rows[0][0], 'pack ' . $datetime . ' != ' . $rows[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestamp
     */
    public function testLocalTime(int $timestamp, AProtocol $protocol)
    {
        try {
            $timestamp .= '.' . rand(0, 9e5);
            $time = \DateTime::createFromFormat('U.u', $timestamp)
                ->format('H:i:s.u');

            //unpack
            $protocol->run('RETURN localtime($t)', [
                't' => $time
            ]);
            $rows = $protocol->pull();
            $this->assertInstanceOf(LocalTime::class, $rows[0][0]);
            $this->assertEquals($time, (string)$rows[0][0], 'unpack ' . $time . ' != ' . $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($t)', [
                't' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            $time = rtrim($time, '.0');
            $this->assertEquals($time, $rows[0][0], 'pack ' . $time . ' != ' . $rows[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testInit
     */
    public function testNode(AProtocol $protocol)
    {
        try {
            $protocol->begin();

            //unpack
            $protocol->run('CREATE (a:Test) RETURN a');
            $rows = $protocol->pull();
            $this->assertInstanceOf(Node::class, $rows[0][0]);

            //pack not supported

            $protocol->rollback();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testInit
     */
    public function testPath(AProtocol $protocol)
    {
        try {
            $protocol->begin();

            //unpack
            $protocol->run('CREATE p=(:Test)-[:HAS]->(:Test) RETURN p');
            $rows = $protocol->pull();
            $this->assertInstanceOf(Path::class, $rows[0][0]);

            foreach ($rows[0][0]->rels() as $rel)
                $this->assertInstanceOf(UnboundRelationship::class, $rel);

            //pack not supported

            $protocol->rollback();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testInit
     */
    public function testPoint2D(AProtocol $protocol)
    {
        try {
            //unpack
            $protocol->run('RETURN point({ latitude: 13.43, longitude: 56.21 })');
            $rows = $protocol->pull();
            $this->assertInstanceOf(Point2D::class, $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($p)', [
                'p' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            $this->assertStringStartsWith('point(', $rows[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testInit
     */
    public function testPoint3D(AProtocol $protocol)
    {
        try {
            //unpack
            $protocol->run('RETURN point({ x: 0, y: 4, z: 1 })');
            $rows = $protocol->pull();
            $this->assertInstanceOf(Point3D::class, $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($p)', [
                'p' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            $this->assertStringStartsWith('point(', $rows[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends testInit
     */
    public function testRelationship(AProtocol $protocol)
    {
        try {
            $protocol->begin();

            //unpack
            $protocol->run('CREATE (:Test)-[rel:HAS]->(:Test) RETURN rel');
            $rows = $protocol->pull();
            $this->assertInstanceOf(Relationship::class, $rows[0][0]);

            //pack not supported

            $protocol->rollback();
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    /**
     * @depends      testInit
     * @dataProvider providerTimestampTimezone
     */
    public function testTime(int $timestamp, string $timezone, AProtocol $protocol)
    {
        try {
            $timestamp .= '.' . rand(0, 9e5);
            $time = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
                ->format('H:i:s.uP');

            //unpack
            $protocol->run('RETURN time($t)', [
                't' => $time
            ]);
            $rows = $protocol->pull();
            $this->assertInstanceOf(Time::class, $rows[0][0]);
            $this->assertEquals($time, (string)$rows[0][0], 'unpack ' . $time . ' != ' . $rows[0][0]);

            //pack
            $protocol->run('RETURN toString($t)', [
                't' => $rows[0][0]
            ]);
            $rows = $protocol->pull();
            // neo4j returns fraction of seconds not padded with zeros ... zero timezone offset returns as Z
            $time = preg_replace(["/\.?0+(.\d{2}:\d{2})$/", "/\+00:00$/"], ['$1', 'Z'], $time);
            $this->assertEquals($time, $rows[0][0], 'pack ' . $time . ' != ' . $rows[0][0]);
        } catch (Exception $e) {
            $this->markTestIncomplete($e->getMessage());
        }
    }

    public function providerTimestamp(): \Generator
    {
        for ($i = 0; $i < self::$iterations; $i++) {
            $ts = $this->randomTimestamp();
            yield 'ts: ' . $ts => [$ts];
        }
    }

    public function providerTimestampTimezone(): \Generator
    {
        for ($i = 0; $i < self::$iterations; $i++) {
            $tz = \DateTimeZone::listIdentifiers()[array_rand(\DateTimeZone::listIdentifiers())];
            $ts = $this->randomTimestamp($tz);
            yield 'ts: ' . $ts . ' tz: ' . $tz => [$ts, $tz];
        }
    }

    /**
     * @param string $timezone
     * @return int
     */
    private function randomTimestamp(string $timezone = '+0000'): int
    {
        try {
            $zone = new \DateTimeZone($timezone);
            $start = new \DateTime(date('Y-m-d H:i:s', strtotime('-10 years', 0)), $zone);
            $end = new \DateTime(date('Y-m-d H:i:s', strtotime('+10 years', 0)), $zone);
            return rand($start->getTimestamp(), $end->getTimestamp());
        } catch (Exception $e) {
            return strtotime('now ' . $timezone);
        }
    }

}
