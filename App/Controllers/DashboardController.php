<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class DashboardController extends Controller
{
    private BookingService $bookingService;
    private StatisticsService $statisticsService;
    private NotificationService $notificationService;
    private UserService $userService;

    public function __construct(
        BookingService $bookingService,
        StatisticsService $statisticsService,
        NotificationService $notificationService,
        UserService $userService
    ) {
        $this->bookingService = $bookingService;
        $this->statisticsService = $statisticsService;
        $this->notificationService = $notificationService;
        $this->userService = $userService;
    }

    /**
     * Render user dashboard view.
     */
    public function userDashboard()
    {
        try {
            // Assume session_start() is already called.
            $user = (object)['id' => $_SESSION['user_id'] ?? null]; // Replace with native session retrieval
            // ...existing code for eager loading if needed...
            $statistics = Cache::remember('user_dashboard_' . $user->id, 60, function () use ($user) {
                return [
                    'total_bookings'     => Booking::where('user_id', $user->id)->count(),
                    'completed_bookings' => Booking::where('user_id', $user->id)->where('status', 'completed')->count(),
                    'total_payments'     => Payment::where('user_id', $user->id)->sum('amount'),
                ];
            });
            view('dashboard/user_dashboard', ['user' => $user, 'statistics' => $statistics]);
        } catch (\Exception $e) {
            error_log('Failed to load user dashboard: '.$e->getMessage());
            abort(500, 'Error loading dashboard');
        }
    }

    /**
     * Fetch user bookings.
     */
    public function getUserBookings(): void
    {
        try {
            $bookings = Booking::where('user_id', $_SESSION['user_id'] ?? null)->get();
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Bookings fetched',
                'data'    => ['bookings' => $bookings]
            ]);
        } catch (\Exception $e) {
            error_log('Failed to fetch bookings: '.$e->getMessage());
            abort(500, 'Failed to fetch bookings');
        }
    }

    /**
     * Fetch dashboard statistics.
     */
    public function fetchStatistics(): void
    {
        try {
            $stats = Cache::remember('dashboard_statistics', 60, function () {
                return [
                    'total_bookings'     => Booking::count(),
                    'completed_bookings' => Booking::where('status', 'completed')->count(),
                    'total_revenue'      => Payment::sum('amount')
                ];
            });
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Statistics fetched',
                'data'    => $stats
            ]);
        } catch (\Exception $e) {
            error_log('Failed to fetch statistics: '.$e->getMessage());
            abort(500, 'Failed to fetch statistics');
        }
    }

    /**
     * Fetch user notifications.
     */
    public function fetchNotifications(): void
    {
        try {
            $notifications = Notification::where('user_id', $_SESSION['user_id'] ?? null)
                ->latest()
                ->get();
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Notifications fetched',
                'data'    => ['notifications' => $notifications]
            ]);
        } catch (\Exception $e) {
            error_log('Failed to fetch notifications: '.$e->getMessage());
            abort(500, 'Failed to fetch notifications');
        }
    }

    /**
     * Fetch user profile.
     */
    public function fetchUserProfile(): void
    {
        try {
            $profile = User::findOrFail($_SESSION['user_id'] ?? null);
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User profile fetched',
                'data'    => ['profile' => $profile]
            ]);
        } catch (\Exception $e) {
            error_log('Failed to fetch user profile: '.$e->getMessage());
            abort(500, 'Failed to fetch user profile');
        }
    }
}
