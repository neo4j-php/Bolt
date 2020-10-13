<?php

namespace Bolt\protocol;

use Bolt\Bolt;
use Bolt\Socket;
use Exception;

/**
 * Class Protocol version 4
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\protocol
 */
class V4 extends V3
{

    public function pullAll(...$args)
    {
        return $this->pull(...$args);
    }

    public function pull(...$args)
    {
        if (empty($args)) {
            $args[0]['n'] = -1;
        }

        try {
            $msg = $this->packer->pack(0x3F, (object)($args[0] ?? []));
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        $output = [];
        do {
            list($signature, $ret) = Socket::read($this->unpacker);
            $output[] = $ret;
        } while ($signature == self::RECORD);

        if ($signature == self::FAILURE) {
            $this->reset();
            Bolt::error($ret['message'], $ret['code']);
            $output = false;
        }

        return $output;
    }

    public function discardAll(...$args): bool
    {
        return $this->discard(...$args);
    }

    public function discard(...$args): bool
    {
        try {
            $msg = $this->packer->pack(0x2F, $args[0] ?? []);
        } catch (Exception $ex) {
            Bolt::error($ex->getMessage());
            return false;
        }

        Socket::write($msg);

        list($signature,) = Socket::read($this->unpacker);
        return $signature == self::SUCCESS;
    }

}
