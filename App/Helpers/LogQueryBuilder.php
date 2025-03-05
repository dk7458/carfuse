<?php

namespace App\Helpers;

use DateTime;
use DateInterval;
use Exception;
use InvalidArgumentException;

/**
 * LogQueryBuilder - Generates SQL queries for audit log operations
 * 
 * This class serves as the single source of truth for all audit log queries,
 * providing standardized methods for building SQL with consistent parameter binding.
 */
class LogQueryBuilder
{
    /**
     * Default pagination values
     */
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;
    
    /**
     * Maximum limits for various operations
     */
    private const MAX_EXPORT_ROWS = 10000;
    private const MAX_BULK_ROWS = 5000;
    
    /**
     * Tables
     */
    private const TABLE_NAME = 'audit_logs';
    
    /**
     * Valid sort fields
     */
    private const VALID_SORT_FIELDS = [
        'id', 'action', 'message', 'user_reference', 
        'booking_reference', 'created_at', 'log_level'
    ];
    
    /**
     * Valid date formats
     */
    private const DATE_FORMATS = [
        'Y-m-d',
        'Y-m-d H:i:s',
        'Y/m/d',
        'Y/m/d H:i:s'
    ];
    
    /**
     * Build WHERE clause and parameters for audit log queries
     *
     * @param array $filters Query filters
     * @return array [whereClause, params]
     */
    public static function buildWhereClause(array $filters): array
    {
        $whereClause = "1=1";
        $params = [];
        
        // User reference filter
        if (!empty($filters['user_id'])) {
            $whereClause .= " AND user_reference = ?";
            $params[] = (int)$filters['user_id'];
        }
        
        // Booking reference filter
        if (!empty($filters['booking_id'])) {
            $whereClause .= " AND booking_reference = ?";
            $params[] = (int)$filters['booking_id'];
        }
        
        // Category/action filter (support both naming conventions)
        if (!empty($filters['category'])) {
            $whereClause .= " AND action = ?";
            $params[] = $filters['category'];
        } elseif (!empty($filters['action'])) {
            $whereClause .= " AND action = ?";
            $params[] = $filters['action'];
        }
        
        // Multi-category filter
        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            $whereClause .= " AND action IN ({$placeholders})";
            foreach ($filters['categories'] as $category) {
                $params[] = $category;
            }
        }
        
        // Log level filter
        if (!empty($filters['log_level'])) {
            $whereClause .= " AND log_level = ?";
            $params[] = $filters['log_level'];
        }
        
        // Multiple log levels filter
        if (!empty($filters['log_levels']) && is_array($filters['log_levels'])) {
            $placeholders = implode(',', array_fill(0, count($filters['log_levels']), '?'));
            $whereClause .= " AND log_level IN ({$placeholders})";
            foreach ($filters['log_levels'] as $level) {
                $params[] = $level;
            }
        }
        
        // Date range filters with better validation
        if (!empty($filters['start_date'])) {
            $startDate = self::validateAndFormatDate($filters['start_date']);
            if ($startDate) {
                $whereClause .= " AND created_at >= ?";
                $params[] = $startDate;
            }
        }
        
        if (!empty($filters['end_date'])) {
            $endDate = self::validateAndFormatDate($filters['end_date'], true);
            if ($endDate) {
                $whereClause .= " AND created_at <= ?";
                $params[] = $endDate;
            }
        }
        
        // Relative date filter (e.g., last 7 days, last month)
        if (!empty($filters['relative_date'])) {
            $dateRange = self::calculateRelativeDateRange($filters['relative_date']);
            if ($dateRange) {
                $whereClause .= " AND created_at >= ? AND created_at <= ?";
                $params[] = $dateRange['start'];
                $params[] = $dateRange['end'];
            }
        }
        
        // Message/details text search
        if (!empty($filters['search'])) {
            $whereClause .= " AND (message LIKE ? OR details LIKE ?)";
            $searchTerm = '%' . SecurityHelper::sanitizeString($filters['search']) . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Request ID filter for correlation
        if (!empty($filters['request_id'])) {
            $whereClause .= " AND request_id = ?";
            $params[] = $filters['request_id'];
        }
        
        // IP address filter
        if (!empty($filters['ip_address'])) {
            $whereClause .= " AND ip_address = ?";
            $params[] = $filters['ip_address'];
        }
        
        // Custom WHERE clause (for advanced filtering)
        if (!empty($filters['custom_where']) && !empty($filters['custom_params']) && is_array($filters['custom_params'])) {
            $whereClause .= " AND ({$filters['custom_where']})";
            foreach ($filters['custom_params'] as $param) {
                $params[] = $param;
            }
        }

        return [$whereClause, $params];
    }
    
    /**
     * Build SQL queries for log retrieval with pagination
     *
     * @param array $filters Various filters to apply
     * @return array Query parts including SQL and parameters
     */
    public static function buildSelectQuery(array $filters): array
    {
        // Build WHERE clause and parameters
        list($whereClause, $params) = self::buildWhereClause($filters);
        
        // Calculate pagination values
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : self::DEFAULT_PAGE;
        $perPage = isset($filters['per_page']) ? 
                  min(max(1, (int)$filters['per_page']), self::MAX_PER_PAGE) : self::DEFAULT_PER_PAGE;
        
        // Build count query
        $countSql = "SELECT COUNT(*) as total FROM " . self::TABLE_NAME . " WHERE {$whereClause}";
        
        // Handle sorting
        $sortField = isset($filters['sort_field']) && in_array($filters['sort_field'], self::VALID_SORT_FIELDS) ? 
                    $filters['sort_field'] : 'created_at';
        
        $sortOrder = isset($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Define fields to select
        $fieldList = self::getFieldList($filters);
        
        // Build main query
        $mainSql = "SELECT {$fieldList} FROM " . self::TABLE_NAME . " WHERE {$whereClause} ORDER BY {$sortField} {$sortOrder}";
        
        // Add pagination limits if not explicitly disabled
        if (!isset($filters['skip_pagination']) || !$filters['skip_pagination']) {
            $offset = ($page - 1) * $perPage;
            $mainSql .= " LIMIT {$perPage} OFFSET {$offset}";
        } else {
            // If pagination disabled, still enforce a reasonable limit
            $limit = min($filters['limit'] ?? self::MAX_BULK_ROWS, self::MAX_BULK_ROWS);
            $mainSql .= " LIMIT {$limit}";
        }
        
        return [
            'countSql' => $countSql,
            'mainSql' => $mainSql,
            'params' => $params,
            'page' => $page,
            'perPage' => $perPage
        ];
    }
    
    /**
     * Build SQL query for direct export to CSV file
     *
     * @param array $filters Filters to apply
     * @param string|null $filepath Optional file path for direct export
     * @return array Export query information
     */
    public static function buildExportQuery(array $filters, ?string $filepath = null): array
    {
        // Get WHERE clause and parameters
        list($whereClause, $params) = self::buildWhereClause($filters);
        
        // Define CSV format options
        $csvOptions = "
            FIELDS TERMINATED BY ',' 
            OPTIONALLY ENCLOSED BY '\"' 
            LINES TERMINATED BY '\\n'";
        
        // Build field list with proper formatting for export
        $selectFields = [
            'id',
            'action as category',
            'log_level',
            'message',
            'user_reference as user_id',
            'booking_reference as booking_id',
            'ip_address',
            'created_at',
            'request_id',
            'details'
        ];
        
        // Allow custom field selection for export
        if (!empty($filters['export_fields']) && is_array($filters['export_fields'])) {
            $selectFields = $filters['export_fields'];
        }
        
        $fieldList = implode(', ', $selectFields);
        
        // Build the main SQL query for export
        $sql = "SELECT {$fieldList} FROM " . self::TABLE_NAME . " WHERE {$whereClause} ";
        
        // Add order by
        $sortField = isset($filters['sort_field']) && in_array($filters['sort_field'], self::VALID_SORT_FIELDS) ? 
                    $filters['sort_field'] : 'created_at';
        $sortOrder = isset($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= "ORDER BY {$sortField} {$sortOrder} ";
        
        // Add reasonable limit to prevent excessive exports
        $limit = min($filters['limit'] ?? self::MAX_EXPORT_ROWS, self::MAX_EXPORT_ROWS);
        $sql .= "LIMIT {$limit}";
        
        // If filepath is provided, add INTO OUTFILE clause
        if ($filepath) {
            // Escape filepath for SQL
            $escapedPath = str_replace('\\', '\\\\', $filepath);
            
            // Add header row as first line
            $headerNames = [
                'ID', 'Category', 'Log Level', 'Message', 'User ID',
                'Booking ID', 'IP Address', 'Created At', 'Request ID', 'Details'
            ];
            
            // Use custom header names if provided
            if (!empty($filters['header_names']) && is_array($filters['header_names']) && 
                count($filters['header_names']) === count($selectFields)) {
                $headerNames = $filters['header_names'];
            }
            
            $headerRow = '"' . implode('","', $headerNames) . '"';
            
            $sql = "SELECT '{$headerRow}' AS header
                    UNION ALL
                    {$sql}
                    INTO OUTFILE '{$escapedPath}'
                    {$csvOptions}";
        }
        
        return [
            'sql' => $sql,
            'params' => $params,
            'limit' => $limit
        ];
    }
    
    /**
     * Build SQL and params for deleting logs
     *
     * @param array $filters Filters to determine which logs to delete
     * @param bool $forceBulkDelete Whether to allow bulk deletion without ID restrictions
     * @return array Delete query information
     */
    public static function buildDeleteQuery(array $filters, bool $forceBulkDelete = false): array
    {
        // Get WHERE clause and parameters
        list($whereClause, $params) = self::buildWhereClause($filters);
        
        // Safeguard: If no specific WHERE conditions and not forcing bulk delete, throw exception
        if (($whereClause === "1=1" || empty(array_filter($params))) && !$forceBulkDelete) {
            throw new InvalidArgumentException('Cannot delete all logs without explicit confirmation');
        }
        
        // Build the select query to get IDs for batch deletion
        $selectSql = "SELECT id FROM " . self::TABLE_NAME . " WHERE {$whereClause}";
        
        // Limit rows for safety if not forced bulk delete
        if (!$forceBulkDelete) {
            $selectSql .= " LIMIT " . self::MAX_EXPORT_ROWS;
        }
        
        // Return query info
        return [
            'select_sql' => $selectSql,
            'params' => $params,
            'where_clause' => $whereClause,
        ];
    }
    
    /**
     * Build SQL for batch deletion of logs
     *
     * @param array $ids Array of log IDs to delete
     * @return string SQL for deletion
     */
    public static function buildBatchDeleteQuery(array $ids): string
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('No IDs provided for deletion');
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return "DELETE FROM " . self::TABLE_NAME . " WHERE id IN ({$placeholders})";
    }
    
    /**
     * Build SQL for retrieving a single log by ID
     *
     * @param int $logId Log ID
     * @return array Query and parameters
     */
    public static function buildGetByIdQuery(int $logId): array
    {
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE id = ? LIMIT 1";
        return [
            'sql' => $sql,
            'params' => [$logId]
        ];
    }
    
    /**
     * Validate and format a date string
     *
     * @param string $dateStr Date string to validate
     * @param bool $isEndDate Whether this is an end date (add time if needed)
     * @return string|null Formatted date string or null if invalid
     */
    private static function validateAndFormatDate(string $dateStr, bool $isEndDate = false): ?string
    {
        try {
            // Try to create DateTime object from the string
            $date = new DateTime($dateStr);
            
            // For end dates, if time component is missing, set it to end of day
            if ($isEndDate && strlen($dateStr) <= 10) { // Simple check for date-only format
                $date->setTime(23, 59, 59);
            }
            
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // Try common date formats if the auto-detection fails
            foreach (self::DATE_FORMATS as $format) {
                $dateObj = DateTime::createFromFormat($format, $dateStr);
                if ($dateObj !== false) {
                    // For end dates without time, set to end of day
                    if ($isEndDate && strlen($format) <= 10) {
                        $dateObj->setTime(23, 59, 59);
                    }
                    return $dateObj->format('Y-m-d H:i:s');
                }
            }
            
            return null; // Invalid date format
        }
    }
    
    /**
     * Calculate a relative date range from a string like "last_7_days"
     *
     * @param string $relativeDate Relative date string ("last_7_days", "last_month", etc.)
     * @return array|null Date range with 'start' and 'end' dates or null if invalid
     */
    private static function calculateRelativeDateRange(string $relativeDate): ?array
    {
        $now = new DateTime();
        $end = $now->format('Y-m-d H:i:s');
        $start = null;
        
        switch ($relativeDate) {
            case 'today':
                $start = (new DateTime())->setTime(0, 0, 0)->format('Y-m-d H:i:s');
                break;
            
            case 'yesterday':
                $start = (new DateTime('yesterday'))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
                $end = (new DateTime('yesterday'))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
                break;
                
            case 'this_week':
                $startWeek = new DateTime('monday this week');
                $start = $startWeek->format('Y-m-d 00:00:00');
                break;
                
            case 'last_week':
                $startWeek = new DateTime('monday last week');
                $endWeek = new DateTime('sunday last week');
                $start = $startWeek->format('Y-m-d 00:00:00');
                $end = $endWeek->format('Y-m-d 23:59:59');
                break;
                
            case 'this_month':
                $start = (new DateTime('first day of this month'))->format('Y-m-d 00:00:00');
                break;
                
            case 'last_month':
                $startMonth = new DateTime('first day of last month');
                $endMonth = new DateTime('last day of last month');
                $start = $startMonth->format('Y-m-d 00:00:00');
                $end = $endMonth->format('Y-m-d 23:59:59');
                break;
                
            case 'last_24_hours':
                $past = clone $now;
                $past->sub(new DateInterval('PT24H'));
                $start = $past->format('Y-m-d H:i:s');
                break;
                
            case 'last_7_days':
                $past = clone $now;
                $past->sub(new DateInterval('P7D'));
                $start = $past->format('Y-m-d H:i:s');
                break;
                
            case 'last_30_days':
                $past = clone $now;
                $past->sub(new DateInterval('P30D'));
                $start = $past->format('Y-m-d H:i:s');
                break;
                
            case 'last_90_days':
                $past = clone $now;
                $past->sub(new DateInterval('P90D'));
                $start = $past->format('Y-m-d H:i:s');
                break;
                
            default:
                // Check for pattern like "last_X_days"
                if (preg_match('/^last_(\d+)_days$/', $relativeDate, $matches)) {
                    $days = (int)$matches[1];
                    if ($days > 0 && $days <= 366) { // Reasonable limit
                        $past = clone $now;
                        $past->sub(new DateInterval("P{$days}D"));
                        $start = $past->format('Y-m-d H:i:s');
                    }
                }
                break;
        }
        
        return $start ? ['start' => $start, 'end' => $end] : null;
    }
    
    /**
     * Get field list for SELECT queries
     *
     * @param array $filters Filter options
     * @return string SQL field list
     */
    private static function getFieldList(array $filters): string
    {
        // If custom fields are specified, use them
        if (!empty($filters['fields']) && is_array($filters['fields'])) {
            return implode(', ', $filters['fields']);
        }
        
        // Default to all fields
        return '*';
    }
}
