<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Services\AuditService;

require_once 'ViewHelper.php';

class AdminDashboardController extends Controller
{
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;
    private AuditService $auditService;
    // Simple in-memory cache
    protected array $cache = [];

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
    }

    /**
     * Custom caching method to replace Illuminate's Cache
     */
    protected function cacheRemember(string $key, int $seconds, callable $callback)
    {
        // Check if we have unexpired cached data
        if (isset($this->cache[$key]) && time() < $this->cache[$key]['expires']) {
            return $this->cache[$key]['data'];
        }
        
        // Generate fresh data
        $data = $callback();
        
        // Store in cache
        $this->cache[$key] = [
            'data' => $data,
            'expires' => time() + $seconds
        ];
        
        return $data;
    }

    /**
     * Helper method to fetch basic metrics used by multiple endpoints
     */
    protected function fetchBasicMetrics(): array
    {
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $totalRefunds = Payment::where('status', 'completed')->where('type', 'refund')->sum('amount');
        
        return [
            'total_users'        => User::count(),
            'active_users'       => User::where('active', true)->count(),
            'inactive_users'     => User::where('active', false)->count(),
            'total_bookings'     => Booking::count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
            'canceled_bookings'  => Booking::where('status', 'canceled')->count(),
            'pending_bookings'   => Booking::where('status', 'pending')->count(),
            'total_revenue'      => $totalRevenue,
            'total_refunds'      => $totalRefunds,
            'net_revenue'        => $totalRevenue - $totalRefunds,
        ];
    }

    /**
     * Helper method to calculate growth trends
     */
    protected function calculateTrends(string $type): array
    {
        $trends = [];
        
        switch ($type) {
            case 'bookings':
                // Calculate booking growth rate
                $currentPeriodBookings = Booking::where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))->count();
                $previousPeriodBookings = Booking::where('created_at', '>=', date('Y-m-d', strtotime('-60 days')))
                    ->where('created_at', '<', date('Y-m-d', strtotime('-30 days')))->count();
                
                if ($previousPeriodBookings > 0) {
                    $trends['booking_growth'] = round((($currentPeriodBookings - $previousPeriodBookings) / $previousPeriodBookings) * 100, 1);
                } else {
                    $trends['booking_growth'] = 0;
                }
                
                // Calculate completion and cancellation rates
                $totalBookings = Booking::count();
                if ($totalBookings > 0) {
                    $trends['completion_rate'] = round((Booking::where('status', 'completed')->count() / $totalBookings) * 100, 1);
                    $trends['cancellation_rate'] = round((Booking::where('status', 'canceled')->count() / $totalBookings) * 100, 1);
                }
                break;
                
            case 'users':
                // Calculate user growth rate
                $currentPeriodUsers = User::where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))->count();
                $previousPeriodUsers = User::where('created_at', '>=', date('Y-m-d', strtotime('-60 days')))
                    ->where('created_at', '<', date('Y-m-d', strtotime('-30 days')))->count();
                
                if ($previousPeriodUsers > 0) {
                    $trends['user_growth'] = round((($currentPeriodUsers - $previousPeriodUsers) / $previousPeriodUsers) * 100, 1);
                } else {
                    $trends['user_growth'] = 0;
                }
                
                // Calculate activity and retention rates
                $totalUsers = User::count();
                if ($totalUsers > 0) {
                    $trends['activity_rate'] = round((User::where('active', true)->count() / $totalUsers) * 100, 1);
                    // Simple retention calculation - users who logged in last month vs. total users
                    $trends['retention_rate'] = round((User::where('last_login_at', '>=', date('Y-m-d', strtotime('-30 days')))->count() / $totalUsers) * 100, 1);
                }
                break;
                
            case 'revenue':
                // Calculate revenue growth
                $currentPeriodRevenue = Payment::where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
                    ->where('status', 'completed')->sum('amount');
                $previousPeriodRevenue = Payment::where('created_at', '>=', date('Y-m-d', strtotime('-60 days')))
                    ->where('created_at', '<', date('Y-m-d', strtotime('-30 days')))
                    ->where('status', 'completed')->sum('amount');
                
                if ($previousPeriodRevenue > 0) {
                    $trends['revenue_growth'] = round((($currentPeriodRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100, 1);
                } else {
                    $trends['revenue_growth'] = 0;
                }
                
                // Calculate refund rate
                $totalRevenue = Payment::where('status', 'completed')->sum('amount');
                $totalRefunds = Payment::where('status', 'completed')->where('type', 'refund')->sum('amount');
                
                if ($totalRevenue > 0) {
                    $trends['refund_rate'] = round(($totalRefunds / $totalRevenue) * 100, 1);
                } else {
                    $trends['refund_rate'] = 0;
                }
                
                // Average value change
                $currentPeriodAvg = Booking::where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
                    ->where('status', 'completed')->avg('amount') ?? 0;
                $previousPeriodAvg = Booking::where('created_at', '>=', date('Y-m-d', strtotime('-60 days')))
                    ->where('created_at', '<', date('Y-m-d', strtotime('-30 days')))
                    ->where('status', 'completed')->avg('amount') ?? 0;
                
                if ($previousPeriodAvg > 0) {
                    $trends['average_value_change'] = round((($currentPeriodAvg - $previousPeriodAvg) / $previousPeriodAvg) * 100, 1);
                } else {
                    $trends['average_value_change'] = 0;
                }
                break;
        }
        
        return $trends;
    }

    /**
     * Helper method for checking authentication
     */
    protected function checkAdminAuth(): void
    {
        requireAuth(); // ensure admin authentication is in place
        
        // Check if user has admin role (assuming this implementation exists)
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Forbidden: Insufficient permissions'
            ]);
            exit;
        }
    }

    /**
     * Helper method for generating consistent API responses
     */
    protected function apiResponse(string $message, array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode([
            'status' => ($statusCode >= 200 && $statusCode < 300) ? 'success' : 'error',
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Main admin dashboard view (HTML)
     */
    public function index(): void
    {
        try {
            $metrics = $this->cacheRemember('dashboard_metrics', 60, function () {
                return $this->fetchBasicMetrics();
            });
            
            $recentBookings = Booking::with('user')->latest()->limit(5)->get();

            // Log this dashboard view in audit logs
            $this->auditService->logEvent(
                'admin_dashboard_viewed',
                'Admin dashboard viewed',
                ['admin_id' => $_SESSION['user_id'] ?? 'unknown'],
                $_SESSION['user_id'] ?? null,
                null,
                'admin'
            );

            extract(compact('metrics', 'recentBookings'));
            include BASE_PATH . '/public/views/admin/dashboard.php';
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Dashboard data API - Get all dashboard data
     */
    public function getDashboardData(): void
    {
        try {
            $this->checkAdminAuth();
            
            $metrics = $this->cacheRemember('dashboard_metrics', 60, function () {
                return $this->fetchBasicMetrics();
            });
            
            $recentBookings = Booking::with('user')->latest()->limit(5)->get();
            $recentUsers = User::latest()->limit(5)->get();
            
            // Calculate trends
            $trends = [
                'user_growth' => $this->calculateTrends('users')['user_growth'] ?? 0,
                'booking_growth' => $this->calculateTrends('bookings')['booking_growth'] ?? 0,
                'revenue_growth' => $this->calculateTrends('revenue')['revenue_growth'] ?? 0
            ];

            // Log this API request in audit logs
            $this->auditService->logEvent(
                'admin_dashboard_data_api',
                'Admin dashboard data API requested',
                ['admin_id' => $_SESSION['user_id'] ?? 'unknown'],
                $_SESSION['user_id'] ?? null,
                null,
                'admin'
            );

            $this->apiResponse('Dashboard data fetched', [
                'metrics' => $metrics,
                'trends' => $trends,
                'recent_bookings' => $recentBookings,
                'recent_users' => $recentUsers
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Get booking statistics endpoint
     */
    public function getBookings(): void
    {
        try {
            $this->checkAdminAuth();
            
            // Get query parameters with defaults
            $period = $_GET['period'] ?? 'month';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $groupBy = $_GET['group_by'] ?? 'day';
            
            // Validate parameters
            if ($period && !in_array($period, ['day', 'week', 'month', 'year'])) {
                $this->apiResponse('Invalid period parameter', [], 400);
            }
            
            if ($groupBy && !in_array($groupBy, ['day', 'week', 'month'])) {
                $this->apiResponse('Invalid group_by parameter', [], 400);
            }
            
            // Process date parameters
            if ($startDate && $endDate) {
                // Validate date formats
                if (!strtotime($startDate) || !strtotime($endDate)) {
                    $this->apiResponse('Invalid date format', [], 400);
                }
                
                // Ensure end date is after start date
                if (strtotime($endDate) < strtotime($startDate)) {
                    $this->apiResponse('End date must be after start date', [], 400);
                }
                
                // Check if date range is too long (366 days max)
                if ((strtotime($endDate) - strtotime($startDate)) > (366 * 86400)) {
                    $this->apiResponse('Date range cannot exceed 366 days', [], 400);
                }
            } else {
                // Set default dates based on period
                switch ($period) {
                    case 'day':
                        $startDate = date('Y-m-d');
                        $endDate = date('Y-m-d');
                        break;
                    case 'week':
                        $startDate = date('Y-m-d', strtotime('-7 days'));
                        $endDate = date('Y-m-d');
                        break;
                    case 'month':
                        $startDate = date('Y-m-d', strtotime('-30 days'));
                        $endDate = date('Y-m-d');
                        break;
                    case 'year':
                        $startDate = date('Y-m-d', strtotime('-365 days'));
                        $endDate = date('Y-m-d');
                        break;
                }
            }
            
            // Create cache key based on parameters
            $cacheKey = "booking_stats_{$startDate}_{$endDate}_{$groupBy}";
            
            $data = $this->cacheRemember($cacheKey, 300, function() use ($startDate, $endDate, $groupBy) {
                // Get booking summary data
                $totalBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();
                $pendingBookings = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'pending')->count();
                $confirmedBookings = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'confirmed')->count();
                $canceledBookings = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'canceled')->count();
                $avgDuration = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->avg('duration') ?? 0;
                $avgAmount = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', '!=', 'canceled')->avg('amount') ?? 0;
                
                // Get timeline data
                $timeline = [];
                
                // Generate timeline based on groupBy
                if ($groupBy === 'day') {
                    $current = strtotime($startDate);
                    $end = strtotime($endDate);
                    
                    while ($current <= $end) {
                        $day = date('Y-m-d', $current);
                        $dayEnd = date('Y-m-d 23:59:59', $current);
                        
                        $timeline[] = [
                            'period' => $day,
                            'new_bookings' => Booking::whereBetween('created_at', [$day, $dayEnd])->count(),
                            'completed_bookings' => Booking::whereBetween('created_at', [$day, $dayEnd])
                                ->where('status', 'completed')->count(),
                            'canceled_bookings' => Booking::whereBetween('created_at', [$day, $dayEnd])
                                ->where('status', 'canceled')->count(),
                            'revenue' => Booking::whereBetween('created_at', [$day, $dayEnd])
                                ->where('status', '!=', 'canceled')->sum('amount')
                        ];
                        
                        $current = strtotime('+1 day', $current);
                    }
                } else if ($groupBy === 'week') {
                    // Similar week-based grouping logic would be implemented here
                    // Omitted for brevity
                } else if ($groupBy === 'month') {
                    // Similar month-based grouping logic would be implemented here
                    // Omitted for brevity
                }
                
                // Get top vehicles data
                $topVehicles = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('vehicle_id, COUNT(*) as bookings_count, SUM(amount) as revenue')
                    ->with('vehicle:id,make,model')
                    ->groupBy('vehicle_id')
                    ->orderByDesc('revenue')
                    ->limit(5)
                    ->get()
                    ->map(function($booking) {
                        return [
                            'vehicle_id' => $booking->vehicle_id,
                            'make' => $booking->vehicle->make ?? 'Unknown',
                            'model' => $booking->vehicle->model ?? 'Unknown',
                            'bookings_count' => $booking->bookings_count,
                            'revenue' => $booking->revenue
                        ];
                    });
                
                return [
                    'summary' => [
                        'total_bookings' => $totalBookings,
                        'pending_bookings' => $pendingBookings,
                        'confirmed_bookings' => $confirmedBookings,
                        'canceled_bookings' => $canceledBookings,
                        'average_duration' => round($avgDuration, 1),
                        'average_amount' => round($avgAmount, 2)
                    ],
                    'trends' => $this->calculateTrends('bookings'),
                    'timeline' => $timeline,
                    'top_vehicles' => $topVehicles
                ];
            });
            
            // Log this API request
            $this->auditService->logEvent(
                'admin_bookings_stats_api',
                'Admin booking statistics API requested',
                [
                    'admin_id' => $_SESSION['user_id'] ?? 'unknown',
                    'period' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                $_SESSION['user_id'] ?? null,
                null,
                'admin'
            );
            
            $this->apiResponse('Booking statistics fetched', $data);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Get user statistics endpoint
     */
    public function getUsers(): void
    {
        try {
            $this->checkAdminAuth();
            
            // Get query parameters with defaults
            $period = $_GET['period'] ?? 'month';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            // Validate parameters
            if ($period && !in_array($period, ['day', 'week', 'month', 'year'])) {
                $this->apiResponse('Invalid period parameter', [], 400);
            }
            
            // Process date parameters similar to getBookings()
            if ($startDate && $endDate) {
                if (!strtotime($startDate) || !strtotime($endDate)) {
                    $this->apiResponse('Invalid date format', [], 400);
                }
                
                if (strtotime($endDate) < strtotime($startDate)) {
                    $this->apiResponse('End date must be after start date', [], 400);
                }
                
                if ((strtotime($endDate) - strtotime($startDate)) > (366 * 86400)) {
                    $this->apiResponse('Date range cannot exceed 366 days', [], 400);
                }
            } else {
                // Set default dates based on period
                switch ($period) {
                    case 'day':
                        $startDate = date('Y-m-d');
                        $endDate = date('Y-m-d');
                        break;
                    case 'week':
                        $startDate = date('Y-m-d', strtotime('-7 days'));
                        $endDate = date('Y-m-d');
                        break;
                    case 'month':
                        $startDate = date('Y-m-d', strtotime('-30 days'));
                        $endDate = date('Y-m-d');
                        break;
                    case 'year':
                        $startDate = date('Y-m-d', strtotime('-365 days'));
                        $endDate = date('Y-m-d');
                        break;
                }
            }
            
            // Create cache key based on parameters
            $cacheKey = "user_stats_{$startDate}_{$endDate}";
            
            $data = $this->cacheRemember($cacheKey, 300, function() use ($startDate, $endDate) {
                // Get user summary data
                $totalUsers = User::count();
                $activeUsers = User::where('active', true)->count();
                $inactiveUsers = $totalUsers - $activeUsers;
                $newUsersCurrentPeriod = User::whereBetween('created_at', [$startDate, $endDate])->count();
                
                // Compare with previous period of equal length
                $previousPeriodStart = date('Y-m-d', strtotime($startDate) - (strtotime($endDate) - strtotime($startDate)));
                $previousPeriodEnd = date('Y-m-d', strtotime($startDate) - 86400); // day before start
                $newUsersPreviousPeriod = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
                
                // Get timeline data
                $timeline = [];
                $current = strtotime($startDate);
                $end = strtotime($endDate);
                
                while ($current <= $end) {
                    $day = date('Y-m-d', $current);
                    $dayEnd = date('Y-m-d 23:59:59', $current);
                    
                    $timeline[] = [
                        'period' => $day,
                        'new_users' => User::whereBetween('created_at', [$day, $dayEnd])->count(),
                        'active_users' => User::where('active', true)
                            ->where(function($query) use ($day, $dayEnd) {
                                $query->whereBetween('last_login_at', [$day, $dayEnd])
                                    ->orWhereBetween('created_at', [$day, $dayEnd]);
                            })->count()
                    ];
                    
                    $current = strtotime('+1 day', $current);
                }
                
                // Get demographic data
                $topLocations = User::selectRaw('location, COUNT(*) as count')
                    ->groupBy('location')
                    ->orderByDesc('count')
                    ->limit(5)
                    ->get()
                    ->map(function($user) {
                        return [
                            'name' => $user->location ?: 'Unknown',
                            'count' => $user->count
                        ];
                    });
                
                // Device breakdown - simplified example
                $deviceBreakdown = [
                    'mobile' => User::where('last_device', 'mobile')->count(),
                    'desktop' => User::where('last_device', 'desktop')->count(),
                    'tablet' => User::where('last_device', 'tablet')->count()
                ];
                
                // Registration sources - simplified example
                $registrationSources = [
                    'direct' => User::where('registration_source', 'direct')->count(),
                    'google' => User::where('registration_source', 'google')->count(),
                    'facebook' => User::where('registration_source', 'facebook')->count(),
                    'referral' => User::where('registration_source', 'referral')->count(),
                    'other' => User::whereNotIn('registration_source', ['direct', 'google', 'facebook', 'referral'])
                        ->orWhereNull('registration_source')->count()
                ];
                
                return [
                    'summary' => [
                        'total_users' => $totalUsers,
                        'active_users' => $activeUsers,
                        'inactive_users' => $inactiveUsers,
                        'new_users_current_period' => $newUsersCurrentPeriod,
                        'new_users_previous_period' => $newUsersPreviousPeriod
                    ],
                    'trends' => $this->calculateTrends('users'),
                    'timeline' => $timeline,
                    'demographics' => [
                        'top_locations' => $topLocations,
                        'device_breakdown' => $deviceBreakdown,
                        'registration_sources' => $registrationSources
                    ]
                ];
            });
            
            // Log this API request
            $this->auditService->logEvent(
                'admin_users_stats_api',
                'Admin user statistics API requested',
                [
                    'admin_id' => $_SESSION['user_id'] ?? 'unknown',
                    'period' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                $_SESSION['user_id'] ?? null,
                null,
                'admin'
            );
            
            $this->apiResponse('User statistics fetched', $data);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Get revenue statistics endpoint
     */
    public function getRevenue(): void
    {
        try {
            $this->checkAdminAuth();
            
            // Get query parameters with defaults
            $period = $_GET['period'] ?? 'month';
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $currency = $_GET['currency'] ?? 'USD';
            
            // Validate parameters
            if ($period && !in_array($period, ['day', 'week', 'month', 'year'])) {
                $this->apiResponse('Invalid period parameter', [], 400);
            }
            
            // Process date parameters similar to previous methods
            if ($startDate && $endDate) {
                if (!strtotime($startDate) || !strtotime($endDate)) {
                    $this->apiResponse('Invalid date format', [], 400);
                }
                
                if (strtotime($endDate) < strtotime($startDate)) {
                    $this->apiResponse('End date must be after start date', [], 400);
                }
                
                if ((strtotime($endDate) - strtotime($startDate)) > (366 * 86400)) {
                    $this->apiResponse('Date range cannot exceed 366 days', [], 400);
                }
            } else {
                // Set default dates based on period
                switch ($period) {
                    case 'day':
                        $startDate = date('Y-m-d');
                        $endDate = date('Y-m-d');
                        break;
                    case 'week':
                        $startDate = date('Y-m-d', strtotime('-7 days'));
                        $endDate = date('Y-m-d');
                        break;
                    case 'month':
                        $startDate = date('Y-m-d', strtotime('-30 days'));
                        $endDate = date('Y-m-d');
                        break;
                    case 'year':
                        $startDate = date('Y-m-d', strtotime('-365 days'));
                        $endDate = date('Y-m-d');
                        break;
                }
            }
            
            // Create cache key based on parameters
            $cacheKey = "revenue_stats_{$startDate}_{$endDate}_{$currency}";
            
            $data = $this->cacheRemember($cacheKey, 300, function() use ($startDate, $endDate, $currency) {
                // Get revenue summary data
                $totalRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->where('type', '!=', 'refund')
                    ->sum('amount');
                
                $totalRefunds = Payment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->where('type', 'refund')
                    ->sum('amount');
                
                $netRevenue = $totalRevenue - $totalRefunds;
                
                $averageBookingValue = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->avg('amount') ?? 0;
                
                // Calculate projected monthly revenue (simple projection)
                $daysInPeriod = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;
                $projectedMonthlyRevenue = ($netRevenue / $daysInPeriod) * 30;
                
                // Get timeline data
                $timeline = [];
                $current = strtotime($startDate);
                $end = strtotime($endDate);
                
                while ($current <= $end) {
                    $day = date('Y-m-d', $current);
                    $dayEnd = date('Y-m-d 23:59:59', $current);
                    
                    $dailyGrossRevenue = Payment::whereBetween('created_at', [$day, $dayEnd])
                        ->where('status', 'completed')
                        ->where('type', '!=', 'refund')
                        ->sum('amount');
                    
                    $dailyRefunds = Payment::whereBetween('created_at', [$day, $dayEnd])
                        ->where('status', 'completed')
                        ->where('type', 'refund')
                        ->sum('amount');
                    
                    $timeline[] = [
                        'period' => $day,
                        'gross_revenue' => $dailyGrossRevenue,
                        'refunds' => $dailyRefunds,
                        'net_revenue' => $dailyGrossRevenue - $dailyRefunds,
                        'bookings' => Booking::whereBetween('created_at', [$day, $dayEnd])->count()
                    ];
                    
                    $current = strtotime('+1 day', $current);
                }
                
                // Get payment methods breakdown
                $paymentMethods = Payment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as amount')
                    ->groupBy('payment_method')
                    ->get()
                    ->mapWithKeys(function($payment) {
                        return [$payment->payment_method => [
                            'count' => $payment->count,
                            'amount' => $payment->amount
                        ]];
                    })->toArray();
                
                // Get top revenue vehicles
                $topRevenueVehicles = Booking::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->selectRaw('vehicle_id, COUNT(*) as bookings, SUM(amount) as revenue')
                    ->with('vehicle:id,make,model')
                    ->groupBy('vehicle_id')
                    ->orderByDesc('revenue')
                    ->limit(5)
                    ->get()
                    ->map(function($booking) {
                        return [
                            'vehicle_id' => $booking->vehicle_id,
                            'make' => $booking->vehicle->make ?? 'Unknown',
                            'model' => $booking->vehicle->model ?? 'Unknown',
                            'revenue' => $booking->revenue,
                            'bookings' => $booking->bookings
                        ];
                    });
                
                return [
                    'summary' => [
                        'total_revenue' => $totalRevenue,
                        'total_refunds' => $totalRefunds,
                        'net_revenue' => $netRevenue,
                        'average_booking_value' => $averageBookingValue,
                        'projected_monthly_revenue' => $projectedMonthlyRevenue
                    ],
                    'trends' => $this->calculateTrends('revenue'),
                    'timeline' => $timeline,
                    'payment_methods' => $paymentMethods,
                    'top_revenue_vehicles' => $topRevenueVehicles
                ];
            });
            
            // Log this API request
            $this->auditService->logEvent(
                'admin_revenue_stats_api',
                'Admin revenue statistics API requested',
                [
                    'admin_id' => $_SESSION['user_id'] ?? 'unknown',
                    'period' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'currency' => $currency
                ],
                $_SESSION['user_id'] ?? null,
                null,
                'admin'
            );
            
            $this->apiResponse('Revenue statistics fetched', $data);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Get platform metrics endpoint
     */
    public function getMetrics(): void
    {
        try {
            $this->checkAdminAuth();
            
            // Create cache key
            $cacheKey = "platform_metrics_" . date('Y-m-d-H');
            
            $data = $this->cacheRemember($cacheKey, 60, function() {
                // System health metrics - these would typically come from actual monitoring systems
                // For this example, we'll use simulated values
                $systemHealth = [
                    'api_response_time' => rand(150, 300), // milliseconds
                    'database_query_time' => rand(50, 120), // milliseconds
                    'server_load' => rand(20, 80) / 100, // 0-1 scale
                    'memory_usage' => rand(40, 90), // percentage
                    'storage_usage' => rand(30, 70) // percentage
                ];
                
                // Current activity metrics
                $activeUsersNow = User::where('last_activity_at', '>=', date('Y-m-d H:i:s', time() - 900))->count();
                $loginsToday = User::where('last_login_at', '>=', date('Y-m-d'))->count();
                
                // Simulated values for API requests and page views
                $apiRequestsToday = rand(10000, 15000);
                $pageViewsToday = rand(2500, 4000);
                
                $activity = [
                    'active_users_now' => $activeUsersNow,
                    'logins_today' => $loginsToday,
                    'api_requests_today' => $apiRequestsToday,
                    'page_views_today' => $pageViewsToday
                ];
                
                // Performance metrics - simulated values
                $performance = [
                    'average_page_load' => rand(8, 15) / 10, // 0.8 to 1.5 seconds
                    'average_api_response' => rand(15, 35) / 100, // 0.15 to 0.35 seconds
                    'error_rate' => rand(1, 5) / 100 // 0.01 to 0.05 (1% to 5%)
                ];
                
                // Fleet status 
                $fleetStatus = [
                    'total_vehicles' => Vehicle::count(),
                    'available_vehicles' => Vehicle::where('status', 'available')->count(),
                    'booked_vehicles' => Vehicle::where('status', 'booked')->count(),
                    'maintenance_vehicles' => Vehicle::where('status', 'maintenance')->count()
                ];
                
                // Business metrics - partially simulated
                $totalVisitors = rand(1000, 2000);
                $bookingAttempts = rand(100, 200);
                $completedBookings = Booking::where('created_at', '>=', date('Y-m-d'))->count();
                
                $businessMetrics = [
                    'conversion_rate' => round(($completedBookings / $bookingAttempts) * 100, 1),
                    'average_session_duration' => rand(120, 180), // seconds
                    'booking_abandonment_rate' => round(($bookingAttempts - $completedBookings) / $bookingAttempts * 100, 1)
                ];
                
                return [
                    'system_health' => $systemHealth,
                    'activity' => $activity,
                    'performance' => $performance,
                    'fleet_status' => $fleetStatus,
                    'business_metrics' => $businessMetrics
                ];
            });
            
            // Log this API request
            $this->auditService->logEvent(
                'admin_platform_metrics_api',
                'Admin platform metrics API requested',
                ['admin_id' => $_SESSION['user_id'] ?? 'unknown'],
                $_SESSION['user_id'] ?? null,
                null,
                'admin'
            );
            
            $this->apiResponse('Platform metrics fetched', $data);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }
}
