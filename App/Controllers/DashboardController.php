<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;
use App\Services\TokenService;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class DashboardController extends Controller
{
    private BookingService $bookingService;
    private StatisticsService $statisticsService;
    private NotificationService $notificationService;
    private UserService $userService;
    private LoggerInterface $logger;

    public function __construct(
        BookingService $bookingService,
        StatisticsService $statisticsService,
        NotificationService $notificationService,
        UserService $userService,
        LoggerInterface $userLogger
    ) {
        $this->bookingService = $bookingService;
        $this->statisticsService = $statisticsService;
        $this->notificationService = $notificationService;
        $this->userService = $userService;
        $this->logger = $userLogger;
    }

    /**
     * Render user dashboard view.
     */
    public function userDashboard()
    {
        try {
            $user = TokenService::getUserFromToken(request()->bearerToken());

            if (!$user) {
                return $this->jsonResponse(['error' => 'Unauthorized'], 401);
            }

            $statistics = Cache::remember('user_dashboard_' . $user->id, 60, function () use ($user) {
                return [
                    'total_bookings'     => Booking::where('user_id', $user->id)->count(),
                    'completed_bookings' => Booking::where('user_id', $user->id)->where('status', 'completed')->count(),
                    'total_payments'     => Payment::where('user_id', $user->id)->sum('amount'),
                ];
            });
            return $this->jsonResponse(['data' => ['user' => $user, 'statistics' => $statistics]]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load user dashboard: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Error loading dashboard'], 500);
        }
    }

    /**
     * Fetch user bookings.
     */
    public function getUserBookings(): void
    {
        try {
            $user = TokenService::getUserFromToken(request()->bearerToken());

            if (!$user) {
                return $this->jsonResponse(['error' => 'Unauthorized'], 401);
            }

            $bookings = Booking::where('user_id', $user->id)->get();
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Bookings fetched',
                'data'    => ['bookings' => $bookings]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch bookings: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to fetch bookings'], 500);
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
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Statistics fetched',
                'data'    => $stats
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch statistics: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to fetch statistics'], 500);
        }
    }

    /**
     * Fetch user notifications.
     */
    public function fetchNotifications(): void
    {
        try {
            $user = TokenService::getUserFromToken(request()->bearerToken());

            if (!$user) {
                return $this->jsonResponse(['error' => 'Unauthorized'], 401);
            }

            $notifications = Notification::where('user_id', $user->id)
                ->latest()
                ->get();
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Notifications fetched',
                'data'    => ['notifications' => $notifications]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch notifications: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to fetch notifications'], 500);
        }
    }

    /**
     * Fetch user profile.
     */
    public function fetchUserProfile(): void
    {
        try {
            $user = TokenService::getUserFromToken(request()->bearerToken());

            if (!$user) {
                return $this->jsonResponse(['error' => 'Unauthorized'], 401);
            }

            $profile = User::findOrFail($user->id);
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'User profile fetched',
                'data'    => ['profile' => $profile]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user profile: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to fetch user profile'], 500);
        }
    }

    private function jsonResponse(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
