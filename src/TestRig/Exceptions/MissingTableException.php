<?php

/**
 * @file
 * Exception to raise when a database table is misssing.
 */

namespace TestRig\Exceptions;

class MissingTableException extends \Exception
{
    /**
     * Implements ::__construct().
     *
     * @param string $table
     *   Table that's missing.
     * @param int $code
     *   As per \Exception.
     * @param \Exception $previous
     *   As per \Exception.
     */
    public function __construct($table, $code = 0, Exception $previous = null)
    {
        // Convert $filename trivially to $message for parent.
        $message = "Missing database table $table";
        parent::__construct($message, $code, $previous);
    }

    /**
     * Error handler to convert older PHP warnings into exceptions.
     *
     * Usage:
     *   set_error_handler(MissingDatasetFileException::errorHandler);
     *   ...
     *   restore_error_handler();
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        preg_match('/no such table: (.*)/', $errstr, $matches);
        if (isset($matches[1]))
        {
            throw new MissingTableException($matches[1]);
        }
        throw new \Exception("Something unexpected happened while trying to handle an error.");
    }
}
