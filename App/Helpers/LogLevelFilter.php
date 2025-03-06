<?php

namespace App\Helpers;

/**
 * Helper class to filter log entries based on their severity level
 */
class LogLevelFilter
{
    /**
     * PSR-3 log levels mapped to their severity (higher number = higher severity)
     */
    private const LOG_LEVEL_MAP = [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600
    ];

    /**
     * The minimum log level that should be processed
     */
    private string $minLevel;

    /**
     * Constructor
     *
     * @param string $minLevel Minimum log level to process, defaults to 'debug' (process all)
     */
    public function __construct(string $minLevel = 'debug')
    {
        $this->minLevel = strtolower($minLevel);
        
        // Fallback to 'debug' if an invalid log level is provided
        if (!isset(self::LOG_LEVEL_MAP[$this->minLevel])) {
            $this->minLevel = 'debug';
        }
    }

    /**
     * Determine if a log entry with the specified level should be processed
     *
     * @param string $level The log level to check
     * @return bool True if the log should be processed, false otherwise
     */
    public function shouldLog(string $level): bool
    {
        $level = strtolower($level);
        
        // Default to highest severity if an invalid level is provided
        $levelValue = self::LOG_LEVEL_MAP[$level] ?? PHP_INT_MAX;
        $minLevelValue = self::LOG_LEVEL_MAP[$this->minLevel];
        
        return $levelValue >= $minLevelValue;
    }

    /**
     * Set the minimum log level
     *
     * @param string $level The new minimum log level
     * @return self
     */
    public function setMinLevel(string $level): self
    {
        if (isset(self::LOG_LEVEL_MAP[strtolower($level)])) {
            $this->minLevel = strtolower($level);
        }
        return $this;
    }

    /**
     * Get the current minimum log level
     *
     * @return string The current minimum log level
     */
    public function getMinLevel(): string
    {
        return $this->minLevel;
    }
}
