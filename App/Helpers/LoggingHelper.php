<?php
namespace App\Helpers;

use Psr\Log\LoggerInterface;

// Ensure the global getLogger function is available
require_once __DIR__ . '/../../logger.php';

class LoggingHelper
{
    /**
     * Retrieve the default logger.
     *
     * @return LoggerInterface
     */
    public static function getDefaultLogger(): LoggerInterface
    {
        return static::getLoggerByCategory('application');
    }

    /**
     * Retrieve a logger by its category.
     *
     * @param string $category
     * @return LoggerInterface
     */
    public static function getLoggerByCategory(string $category): LoggerInterface
    {
        // Call the global getLogger() function defined in the root logger configuration.
        return \getLogger($category);
    }
}
