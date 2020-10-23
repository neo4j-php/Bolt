<?php

namespace Bolt\PackStream;

/**
 * Interface IPacker
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\PackStream
 */
interface IPacker
{
    /**
     * @param $signature
     * @param mixed ...$params
     * @return string
     */
    public function pack($signature, ...$params): string;
}
