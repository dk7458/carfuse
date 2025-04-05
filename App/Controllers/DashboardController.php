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
    private MetricsService $metricsService;
    private NotificationService $notificationService;
    private UserService $userService;
    private AuditService $auditService;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        BookingService $bookingService,
        MetricsService $metricsService,
        NotificationService $notificationService,
        UserService $userService,
        AuditService $auditService,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->bookingService = $bookingService;
        $this->metricsService = $metricsService;
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
            
            // Log dashboard access
            $this->auditService->logEvent(
                'dashboard_accessed',
                "User accessed their dashboard",
                ['user_id' => $user->id],
                $user->id,
                null,
                'user'
            );
            
            // Render the main dashboard view - the components will load via HTMX
            include BASE_PATH . '/public/views/dashboard.php';
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Get user statistics (for HTMX).
     */
    public function getUserStatistics(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            $statistics = Cache::remember('user_dashboard_' . $userId, 60, function () use ($userId) {
                return [
                    'total_bookings'     => Booking::where('user_id', $userId)->count(),
                    'completed_bookings' => Booking::where('user_id', $userId)->where('status', 'completed')->count(),
                    'total_payments'     => Payment::where('user_id', $userId)->sum('amount'),
                ];
            });
            
            // Include the statistics partial
            include BASE_PATH . '/public/views/partials/user-statistics.php';
        } catch (\Exception $e) {
            echo '<div class="text-red-500 p-4">Nie udało się załadować statystyk.</div>';
            $this->logger->error("Failed to load user statistics", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Fetch user bookings (for HTMX).
     */
    public function getUserBookings(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $limit = 5;
            $offset = $page * $limit;
            
            $bookings = Booking::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
            
            // Log bookings fetch
            $this->auditService->logEvent(
                'bookings_fetched',
                "User fetched their bookings",
                ['user_id' => $userId],
                $userId,
                null,
                'booking'
            );
            
            // Include the bookings partial
            include BASE_PATH . '/public/views/partials/user-bookings.php';
        } catch (\Exception $e) {
            echo '<div class="text-red-500 p-4">Nie udało się załadować rezerwacji.</div>';
            $this->logger->error("Failed to load user bookings", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Fetch user profile (for HTMX).
     */
    public function getUserProfile(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            $profile = User::findOrFail($userId);
            
            // Prepare additional profile data if needed
            $profileData = [
                'id' => $profile->id,
                'name' => $profile->name,
                'email' => $profile->email,
                'avatar_url' => $profile->avatar_url ?? '/images/default-avatar.png',
                'bio' => $profile->bio ?? '',
                'location' => $profile->location ?? '',
                'joined_date' => (new \DateTime($profile->created_at))->format('d.m.Y'),
            ];
            
            // Log profile fetch
            $this->auditService->logEvent(
                'profile_fetched',
                "User fetched their profile",
                ['user_id' => $userId],
                $userId,
                null,
                'user'
            );
            
            // Include the updated profile partial
            include BASE_PATH . '/public/views/partials/user-profile.php';
        } catch (\Exception $e) {
            echo '<div class="text-red-500 p-4">Nie udało się załadować profilu.</div>';
            $this->logger->error("Failed to load user profile", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Fetch user notifications (for HTMX).
     */
    public function getUserNotifications(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $limit = 5;
            $offset = $page * $limit;
            
            $notifications = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->offset($offset)
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
            
            // Include the notifications partial
            include BASE_PATH . '/public/views/partials/user-notifications.php';
        } catch (\Exception $e) {
            echo '<div class="text-red-500 p-4">Nie udało się załadować powiadomień.</div>';
            $this->logger->error("Failed to load user notifications", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
        }
    }
}
