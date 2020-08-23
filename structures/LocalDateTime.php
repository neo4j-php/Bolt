<?php

namespace Bolt\structures;

/**
 * Class LocalDateTime
 * Immutable
 *
 * An instant capturing the date and the time, but not the time zone
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 * @package Bolt\structures
 */
class LocalDateTime
{

    /**
     * @var int
     */
    private $seconds;

    /**
     * @var int
     */
    private $nanoseconds;

    /**
     * LocalDateTime constructor.
     * @param int $seconds
     * @param int $nanoseconds
     */
    public function __construct(int $seconds, int $nanoseconds)
    {
        $this->seconds = $seconds;
        $this->nanoseconds = $nanoseconds;
    }

    /**
     * seconds since the Unix epoch
     * @return int
     */
    public function seconds(): int
    {
        return $this->seconds;
    }

    /**
     * @return int
     */
    public function nanoseconds(): int
    {
        return $this->nanoseconds;
    }

}
