<?php

namespace App\Controllers;

use App\Services\BookingService;

class DashboardController
{
    private BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Render user dashboard view
     */
    public function userDashboard()
    {
        require_once __DIR__ . '/../views/dashboard/user_dashboard.php';
    }

    /**
     * Fetch user bookings
     */
    public function getUserBookings(): void
    {
        $userId = $_SESSION['user_id']; // Replace with actual session logic

        try {
            $bookings = $this->bookingService->getUserBookings($userId);
            echo json_encode(['status' => 'success', 'bookings' => $bookings]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
