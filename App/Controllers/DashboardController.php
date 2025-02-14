<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
     * Render user dashboard view using Eloquent ORM and caching.
     */
    public function userDashboard()
    {
        try {
            // Eager load bookings and notifications for the authenticated user
            $user = Auth::user()->load(['bookings', 'notifications']);
            $statistics = Cache::remember('user_dashboard_' . $user->id, 60, function () use ($user) {
                return [
                    'total_bookings'     => Booking::where('user_id', $user->id)->count(),
                    'completed_bookings' => Booking::where('user_id', $user->id)->where('status', 'completed')->count(),
                    'total_payments'     => Payment::where('user_id', $user->id)->sum('amount'),
                ];
            });
            view('dashboard/user_dashboard', ['user' => $user, 'statistics' => $statistics]);
        } catch (\Exception $e) {
            Log::channel('dashboard')->error('Failed to load user dashboard', ['error' => $e->getMessage()]);
            abort(500, 'Error loading dashboard');
        }
    }

    /**
     * Fetch user bookings using Eloquent ORM.
     */
    public function getUserBookings(): void
    {
        try {
            $bookings = Booking::where('user_id', Auth::id())->get();
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Bookings fetched',
                'data'    => ['bookings' => $bookings]
            ]);
        } catch (\Exception $e) {
            Log::channel('dashboard')->error('Failed to fetch bookings', ['error' => $e->getMessage()]);
            abort(500, 'Failed to fetch bookings');
        }
    }

    /**
     * Fetch dashboard statistics using Eloquent ORM and caching.
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
            Log::channel('dashboard')->error('Failed to fetch statistics', ['error' => $e->getMessage()]);
            abort(500, 'Failed to fetch statistics');
        }
    }

    /**
     * Fetch user notifications using Eloquent ORM.
     */
    public function fetchNotifications(): void
    {
        try {
            $notifications = Notification::where('user_id', Auth::id())
                ->latest()
                ->get();
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Notifications fetched',
                'data'    => ['notifications' => $notifications]
            ]);
        } catch (\Exception $e) {
            Log::channel('dashboard')->error('Failed to fetch notifications', ['error' => $e->getMessage()]);
            abort(500, 'Failed to fetch notifications');
        }
    }

    /**
     * Fetch user profile using Eloquent ORM.
     */
    public function fetchUserProfile(): void
    {
        try {
            $profile = User::findOrFail(Auth::id());
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User profile fetched',
                'data'    => ['profile' => $profile]
            ]);
        } catch (\Exception $e) {
            Log::channel('dashboard')->error('Failed to fetch user profile', ['error' => $e->getMessage()]);
            abort(500, 'Failed to fetch user profile');
        }
    }
}
