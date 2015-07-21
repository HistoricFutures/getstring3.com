<?php

/**
 * @file
 * A log of requests made during a chain.
 */

namespace TestRig\Models;

/**
 * @class
 * Log
 */
class Log
{
    // Incremental time.
    private $timeSoFar = 0;
    // Log of activity.
    private $log = array();

    /**
     * Log an interaction between agents.
     *
     * @param mixed $from
     *   ID of thing instigating interaction.
     * @param mixed $to
     *   ID of thing responding to interaction (even if with a "no-response").
     */
    public function logInteraction($from, $to, $timeDifference)
    {
        // Prepare a log item and add it to the log, updating the incremental
        // timestamp as we go.
        $logItem = array(
            'from' => $from,
            'to' => $to,
            'start' => $this->timeSoFar,
        );
        $logItem['end'] = $this->timeSoFar = $this->timeSoFar + $timeDifference;
        $this->log[] = $logItem;
    }

    /**
     * Return the log accrued so far.
     *
     * @return array $log
     *   Full log so far.
     */
    public function getLog()
    {
        return $this->log;
    }
}
