<?php

namespace App\Controllers;

use App\Services\BookingService;
use App\Services\StatisticsService;
use App\Services\NotificationService;
use App\Services\UserService;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class DashboardController
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
     * Render user dashboard view
     */
    public function userDashboard()
    {
        view('dashboard/user_dashboard');
    }

    /**
     * Fetch user bookings
     */
    public function getUserBookings(): void
    {
        try {
            $userId = requireAuth(); // get authenticated user id
            $bookings = $this->bookingService->getUserBookings($userId);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Bookings fetched', 'data' => ['bookings' => $bookings]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch bookings', 'data' => []]);
        }
        exit;
    }

    /**
     * Fetch dashboard statistics
     */
    public function fetchStatistics(): void
    {
        try {
            $statistics = $this->statisticsService->getDashboardStatistics();
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Statistics fetched', 'data' => $statistics]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch statistics', 'data' => []]);
        }
        exit;
    }

    /**
     * Fetch user notifications
     */
    public function fetchNotifications(): void
    {
        try {
            $userId = requireAuth(); // get authenticated user id
            $notifications = $this->notificationService->getUserNotifications($userId);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Notifications fetched', 'data' => ['notifications' => $notifications]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch notifications', 'data' => []]);
        }
        exit;
    }

    /**
     * Fetch user profile information
     */
    public function fetchUserProfile(): void
    {
        try {
            $userId = requireAuth(); // get authenticated user id
            $profile = $this->userService->getUserProfile($userId);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'User profile fetched', 'data' => ['profile' => $profile]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch user profile', 'data' => []]);
        }
        exit;
    }
}
