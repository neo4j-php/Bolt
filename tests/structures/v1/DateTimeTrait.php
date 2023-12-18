<?php

namespace Bolt\tests\structures\v1;

use Bolt\protocol\{
    AProtocol,
    V1,
    V4_3,
    V5
};

trait DateTimeTrait
{
    /**
     * @depends testInit
     * @dataProvider providerTimestampTimezone
     */
    public function testDateTime(int $timestamp, string $timezone, AProtocol|V1|V4_3|V5 $protocol): void
    {
        $timestamp .= '.' . rand(0, 9e5);
        $datetime = \DateTime::createFromFormat('U.u', $timestamp, new \DateTimeZone($timezone))
            ->format('Y-m-d\TH:i:s.uP');

        //unpack
        $res = iterator_to_array(
            $protocol->run('RETURN datetime($date)', [
                'date' => $datetime
            ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );
        $dateTimeStructure = $res[1]->content[0];

        $this->assertInstanceOf($this->expectedDateTimeClass, $dateTimeStructure);
        $this->assertEquals($datetime, (string)$dateTimeStructure, 'unpack ' . $datetime . ' != ' . $dateTimeStructure);

        //pack
        $res = iterator_to_array(
            $protocol
                ->run('RETURN toString($date)', [
                    'date' => $dateTimeStructure
                ], ['mode' => 'r'])
                ->pull()
                ->getResponses(),
            false
        );

        // neo4j returns fraction of seconds not padded with zeros ... zero timezone offset returns as Z
        $datetime = preg_replace(["/\.?0+(.\d{2}:\d{2})$/", "/\+00:00$/"], ['$1', 'Z'], $datetime);
        $this->assertEquals($datetime, $res[1]->content[0], 'pack ' . $datetime . ' != ' . $res[1]->content[0]);
    }
}
