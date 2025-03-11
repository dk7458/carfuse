<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\User;
use App\Services\AuditService;
use App\Helpers\ExceptionHandler;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;
use App\Services\BookingService;
use App\Services\MetricsService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Helpers\ViewHelper;

require_once   'ViewHelper.php';

class DashboardController extends Controller
{
    private BookingService $bookingService;
    private MetricsService $statisticsService;
    private NotificationService $notificationService;
    private UserService $userService;
    private AuditService $auditService;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        BookingService $bookingService,
        MetricsService $statisticsService,
        NotificationService $notificationService,
        UserService $userService,
        AuditService $auditService,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->bookingService = $bookingService;
        $this->MetricsService = $statisticsService;
        $this->notificationService = $notificationService;
        $this->userService = $userService;
        $this->auditService = $auditService;
        $this->exceptionHandler = $exceptionHandler;
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
            
            // Log dashboard access
            $this->auditService->logEvent(
                'dashboard_accessed',
                "User accessed their dashboard",
                ['user_id' => $user->id],
                $user->id,
                null,
                'user'
            );
            
            view('dashboard/user_dashboard', ['user' => $user, 'statistics' => $statistics]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Fetch user bookings.
     */
    public function getUserBookings(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $bookings = Booking::where('user_id', $userId)->get();
            
            // Log bookings fetch
            $this->auditService->logEvent(
                'bookings_fetched',
                "User fetched their bookings",
                ['user_id' => $userId],
                $userId,
                null,
                'booking'
            );
            
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Bookings fetched',
                'data'    => ['bookings' => $bookings]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Fetch dashboard statistics.
     */
    public function fetchStatistics(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            $stats = Cache::remember('dashboard_statistics', 60, function () {
                return [
                    'total_bookings'     => Booking::count(),
                    'completed_bookings' => Booking::where('status', 'completed')->count(),
                    'total_revenue'      => Payment::sum('amount')
                ];
            });
            
            // Log statistics fetch
            $this->auditService->logEvent(
                'statistics_fetched',
                "User fetched dashboard statistics",
                ['user_id' => $userId],
                $userId,
                null,
                'user'
            );
            
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Statistics fetched',
                'data'    => $stats
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Fetch user notifications.
     */
    public function fetchNotifications(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            $notifications = Notification::where('user_id', $userId)
                ->latest()
                ->get();
                
            // Log notifications fetch
            $this->auditService->logEvent(
                'notifications_fetched',
                "User fetched their notifications",
                ['user_id' => $userId],
                $userId,
                null,
                'notification'
            );
            
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Notifications fetched',
                'data'    => ['notifications' => $notifications]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Fetch user profile.
     */
    public function fetchUserProfile(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $profile = User::findOrFail($userId);
            
            // Log profile fetch
            $this->auditService->logEvent(
                'profile_fetched',
                "User fetched their profile",
                ['user_id' => $userId],
                $userId,
                null,
                'user'
            );
            
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User profile fetched',
                'data'    => ['profile' => $profile]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }
}
