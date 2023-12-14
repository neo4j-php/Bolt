<?php

namespace Bolt\protocol;

/**
 * Class ServerState ..keep track of assumed server state
 * @author Michal Stefanak
 * @link https://github.com/neo4j-php/Bolt
 * @package Bolt
 * @see https://www.neo4j.com/docs/bolt/current/bolt/server-state/
 */
class ServerState
{
    /**
     * Internal pointer for current server state
     */
    private \Bolt\enum\ServerState $current = \Bolt\enum\ServerState::DISCONNECTED;

    /**
     * @param \Bolt\enum\ServerState[] $expected
     * @var callable(\Bolt\enum\ServerState $current, array $expected)
     */
    public $expectedServerStateMismatchCallback;

    /**
     * Get current server state
     */
    public function get(): \Bolt\enum\ServerState
    {
        return $this->current;
    }

    /**
     * Set current server state
     */
    public function set(\Bolt\enum\ServerState $state): void
    {
        $this->current = $state;
    }

    /**
     * Check if current server state equals one of requested
     */
    public function is(\Bolt\enum\ServerState ...$states): bool
    {
        if (in_array($this->current, $states)) {
            return true;
        }

        if (is_callable($this->expectedServerStateMismatchCallback))
            ($this->expectedServerStateMismatchCallback)($this->current, $states);
        return false;
    }
}
