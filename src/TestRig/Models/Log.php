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
     * @param float $ackTimeDifference
     *   Time passsing between initiation and ack.
     * @param float $answerTimeDifference
     *   Time passsing between ack and answer.
     */
    public function logInteraction($from, $to, $ackTimeDifference = null, $answerTimeDifference = null)
    {
        // Prepare a log item and add it to the log, updating the incremental
        // timestamp as we go.
        $logItem = array(
            'from' => $from,
            'to' => $to,
            'start' => $this->timeSoFar,
        );

        // Ack and answer could be either null.
        if ($ackTimeDifference !== null) {
            $logItem['ack'] = $this->timePasses($ackTimeDifference);
        }
        if ($answerTimeDifference !== null) {
            $logItem['answer'] = $this->timePasses($answerTimeDifference);
        }

        $this->log[] = $logItem;
    }

    /**
     * Increment internal clock.
     *
     * @param float $timeDifference = 0
     *   Time difference by which to increment internal clock.
     * @return float
     *   New internal time.
     */
    public function timePasses($timeDifference = 0)
    {
        return ($this->timeSoFar += $timeDifference);
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

    /**
     * Travel back in time, to simulate parallel bifurcating routes.
     *
     * @param float $time
     *    Internal clock time to travel back to.
     */
    public function timeTravelTo($time)
    {
        $this->timeSoFar = $time;
    }
}
