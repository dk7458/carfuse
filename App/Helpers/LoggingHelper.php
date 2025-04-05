<?php
namespace App\Helpers;

use Psr\Log\LoggerInterface;

/**
 * @deprecated This class is deprecated. Use dependency injection to get loggers directly.
 */
class LoggingHelper
{
    private LoggerInterface $defaultLogger;
    private array $categoryLoggers = [];
    
    /**
     * Constructor with dependency injection
     * 
     * @param LoggerInterface $defaultLogger The default logger instance
     * @param array $categoryLoggers Optional array of category-specific loggers
     */
    public function __construct(LoggerInterface $defaultLogger, array $categoryLoggers = [])
    {
        $this->defaultLogger = $defaultLogger;
        $this->categoryLoggers = $categoryLoggers;
        
        trigger_error('LoggingHelper is deprecated. Use dependency injection to get logger instances directly.', E_USER_DEPRECATED);
    }
    
    /**
     * @deprecated Use dependency injection instead
     */
    public function getDefaultLogger(): LoggerInterface
    {
        return $this->defaultLogger;
    }

    /**
     * @deprecated Use dependency injection instead
     */
    public function getLoggerByCategory(string $category): LoggerInterface
    {
        return $this->categoryLoggers[$category] ?? $this->defaultLogger;
    }
}
