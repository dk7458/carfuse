<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class AdminDashboardController
{
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

            view('admin/dashboard', ['metrics' => $metrics, 'recentBookings' => $recentBookings]);
        } catch (\Exception $e) {
            Log::channel('dashboard')->error('Failed to load admin dashboard', ['error' => $e->getMessage()]);
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

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Dashboard data fetched',
                'data'    => [
                    'metrics'         => $metrics,
                    'recent_bookings' => $recentBookings,
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('dashboard')->error('Failed to fetch dashboard data', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to fetch dashboard data',
                'data'    => []
            ]);
        }
    }
}
