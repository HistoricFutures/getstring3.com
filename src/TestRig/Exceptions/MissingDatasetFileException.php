<?php

/**
 * @file
 * Exception to raise when a Dataset file is missing.
 */

namespace TestRig\Exceptions;

class MissingDatasetFileException extends \Exception
{
    /**
     * Implements ::__construct().
     *
     * @param string $filename
     *   Filename that's missing.
     * @param int $code
     *   As per \Exception.
     * @param \Exception $previous
     *   As per \Exception.
     */
    public function __construct($filename, $code = 0, Exception $previous = null)
    {
        // Convert $filename trivially to $message for parent.
        $message = "Missing Dataset file $filename";
        parent::__construct($message, $code, $previous);
    }
}
