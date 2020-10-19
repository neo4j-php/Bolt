<?php

namespace Bolt\PackStream;

/**
 * Interface IUnpacker
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\PackStream
 */
interface IUnpacker
{
    /**
     * @param string $msg
     * @param int $signature
     * @return mixed
     */
    public function unpack(string $msg, int &$signature);
}
