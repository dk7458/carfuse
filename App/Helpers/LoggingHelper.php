<?php
namespace App\Helpers;

use Psr\Log\LoggerInterface;

/**
 * @deprecated This class is deprecated. Use global functions from logger.php instead:
 * - getLogger(string $category): LoggerInterface
 * - getDefaultLogger(): LoggerInterface
 */
class LoggingHelper
{
    /**
     * @deprecated Use getDefaultLogger() function from logger.php instead
     */
    public static function getDefaultLogger(): LoggerInterface
    {
        trigger_error('LoggingHelper is deprecated. Use getDefaultLogger() function directly.', E_USER_DEPRECATED);
        return \getDefaultLogger();
    }

    /**
     * @deprecated Use getLogger($category) function from logger.php instead
     */
    public static function getLoggerByCategory(string $category): LoggerInterface
    {
        trigger_error('LoggingHelper is deprecated. Use getLogger(category) function directly.', E_USER_DEPRECATED);
        return \getLogger($category);
    }
}
