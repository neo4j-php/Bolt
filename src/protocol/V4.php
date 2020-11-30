<?php

namespace Bolt\protocol;

use Exception;
use Bolt\error\MessageException;

/**
 * Class Protocol version 4
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
class V4 extends V3
{

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function pullAll(...$args)
    {
        return $this->pull(...$args);
    }

    /**
     * @param mixed ...$args
     * @return array
     * @throws Exception
     */
    public function pull(...$args)
    {
        $this->write($this->packer->pack(0x3F, $args[0]));

        $output = [];
        do {
            $ret = $this->read($signature);
            $output[] = $ret;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $last = array_pop($output);
            throw new MessageException($last['message'] . ' (' . $last['code'] . ')');
        }

        return $output;
    }

    /**
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function discardAll(...$args): bool
    {
        return $this->discard(...$args);
    }

    /**
     * @param mixed ...$args
     * @return bool
     * @throws Exception
     */
    public function discard(...$args): bool
    {
        $this->write($this->packer->pack(0x2F, $args[0]));
        $this->read($signature);

        return $signature == self::SUCCESS;
    }

}
