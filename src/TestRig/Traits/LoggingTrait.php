<?php

/**
 * @file
 * Trait to handle logging.
 */

namespace TestRig\Traits;

use TestRig\Core\ConfiguredSilex;

/**
 * @class
 * LoggingTrait.
 */
trait LoggingTrait
{
    // Logger.
    private $logger = null;

    /**
     * Detect logging.
     */
    public function detectLogging()
    {
        if ($app = ConfiguredSilex::getLastApp()) {
            $this->logger = $app['monolog'];
        }
    }
    
    /**
     * Log a debug message conditional on having logging.
     *
     * @param string $message
     *    Debug message.
     * @param array $context = []
     *    Monolog context.
     */
    public function addDebugIfExists($message, $context = [])
    {
        $this->logger && $this->logger->addDebug($message, $context);
    }
}
