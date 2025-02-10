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
        $userId = requireAuth(); // get authenticated user id
        try {
            $bookings = $this->bookingService->getUserBookings($userId);
            echo json_encode(['status' => 'success', 'bookings' => $bookings]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Fetch dashboard statistics
     */
    public function fetchStatistics(): void
    {
        try {
            $statistics = $this->statisticsService->getDashboardStatistics();
            echo json_encode(['status' => 'success', 'data' => $statistics]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Fetch user notifications
     */
    public function fetchNotifications(): void
    {
        $userId = requireAuth(); // get authenticated user id
        try {
            $notifications = $this->notificationService->getUserNotifications($userId);
            echo json_encode(['status' => 'success', 'notifications' => $notifications]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Fetch user profile information
     */
    public function fetchUserProfile(): void
    {
        $userId = requireAuth(); // get authenticated user id
        try {
            $profile = $this->userService->getUserProfile($userId);
            echo json_encode(['status' => 'success', 'profile' => $profile]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
