<?php

/**
 * @file
 * Exception to raise when a file is missing.
 */

namespace TestRig\Exceptions;

class MissingFileException extends \Exception
{
    /**
     * Implements ::__construct().
     *
     * @param string $filename
     *   Name of missing file.
     * @param int $code
     *   As per \Exception.
     * @param \Exception $previous
     *   As per \Exception.
     */
    public function __construct($filename, $code = 0, Exception $previous = null)
    {
        // Convert $filename trivially to $message for parent.
        $message = "Missing file $filename";
        parent::__construct($message, $code, $previous);
    }
}
