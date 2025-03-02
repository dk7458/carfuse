<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Services\AuditService;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class AdminDashboardController extends Controller
{
    protected LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuditService $auditService;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService
    ) {
        parent::__construct($logger);
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
    }

    public function index(): void
    {
        try {
            $metrics = Cache::remember('dashboard_metrics', 60, function () {
                $totalRevenue = Payment::where('status', 'completed')->sum('amount');
                $totalRefunds = Payment::where('status', 'completed')->where('type', 'refund')->sum('amount');
                return [
                    'total_users'        => User::count(),
                    'active_users'       => User::where('active', true)->count(),
                    'total_bookings'     => Booking::count(),
                    'completed_bookings' => Booking::where('status', 'completed')->count(),
                    'canceled_bookings'  => Booking::where('status', 'canceled')->count(),
                    'total_revenue'      => $totalRevenue,
                    'total_refunds'      => $totalRefunds,
                    'net_revenue'        => $totalRevenue - $totalRefunds,
                ];
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

    public function getDashboardData(): void
    {
        try {
            requireAuth(); // ensure admin authentication is in place
            
            $metrics = Cache::remember('dashboard_metrics', 60, function () {
                $totalRevenue = Payment::where('status', 'completed')->sum('amount');
                $totalRefunds = Payment::where('status', 'completed')->where('type', 'refund')->sum('amount');
                return [
                    'total_users'        => User::count(),
                    'active_users'       => User::where('active', true)->count(),
                    'total_bookings'     => Booking::count(),
                    'completed_bookings' => Booking::where('status', 'completed')->count(),
                    'canceled_bookings'  => Booking::where('status', 'canceled')->count(),
                    'total_revenue'      => $totalRevenue,
                    'total_refunds'      => $totalRefunds,
                    'net_revenue'        => $totalRevenue - $totalRefunds,
                ];
            });
            $recentBookings = Booking::with('user')->latest()->limit(5)->get();

            // Log this API request in audit logs
            $this->auditService->logEvent(
                'admin_dashboard_data_api',
                'Admin dashboard data API requested',
                ['admin_id' => $_SESSION['user_id'] ?? 'unknown'],
                $_SESSION['user_id'] ?? null,
                null,
                'admin'
            );

            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Dashboard data fetched',
                'data'    => [
                    'metrics'         => $metrics,
                    'recent_bookings' => $recentBookings,
                ]
            ]);
            exit;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }
}
