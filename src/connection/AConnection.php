<?php

namespace Bolt\connection;

/**
 * Class AConnection
 *
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt\connection
 */
abstract class AConnection implements IConnection
{
    protected string $ip;
    protected int $port;
    protected float $timeout;

    /**
     * @inheritDoc
     */
    public function __construct(string $ip = '127.0.0.1', int $port = 7687, float $timeout = 15)
    {
        if (filter_var($ip, FILTER_VALIDATE_URL)) {
            $scheme = parse_url($ip, PHP_URL_SCHEME);
            if (!empty($scheme)) {
                $ip = str_replace($scheme . '://', '', $ip);
            }
        }

        $this->ip = $ip;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Print buffer as HEX
     * @param string $str
     * @param string $prefix
     */
    protected function printHex(string $str, string $prefix = 'C: ')
    {
        $str = implode(unpack('H*', $str));
        echo '<pre>' . $prefix;
        foreach (str_split($str, 8) as $chunk) {
            echo implode(' ', str_split($chunk, 2));
            echo '    ';
        }
        echo '</pre>' . PHP_EOL;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function setTimeout(float $timeout)
    {
        $this->timeout = $timeout;
    }
}
