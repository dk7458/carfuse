<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Helpers\LoggingHelper;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class AdminDashboardController
{
    private $logger;

    public function __construct()
    {
        $this->logger = LoggingHelper::getLoggerByCategory('admin_dashboard');
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

            extract(compact('metrics', 'recentBookings'));
            include BASE_PATH . '/public/views/admin/dashboard.php';
        } catch (\Exception $e) {
            $this->logger->error("DASHBOARD ERROR: " . $e->getMessage());
            http_response_code(500);
            echo 'Error loading the dashboard. Please try again later.';
        }
    }

    public function getDashboardData(): void
    {
        requireAuth(); // ensure admin authentication is in place
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
            $this->logger->error("DASHBOARD ERROR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to fetch dashboard data',
                'data'    => []
            ]);
            exit;
        }
    }
}
