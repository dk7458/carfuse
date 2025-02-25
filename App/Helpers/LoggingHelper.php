<?php
namespace App\Helpers;

use Psr\Log\LoggerInterface;

class LoggingHelper
{
    /**
     * Retrieve the default logger.
     *
     * @return LoggerInterface
     */
    public function getDefaultLogger(): LoggerInterface
    {
        return $this->getLoggerByCategory('application');
    }

    /**
     * Retrieve a logger by its category.
     *
     * @param string $category
     * @return LoggerInterface
     */
    public function getLoggerByCategory(string $category): LoggerInterface
    {
        // Call the global getLogger() function defined in the root logger configuration.
        return \getLogger($category);
    }
}
