<?php

namespace App\Controllers;

use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\UserService;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class AdminDashboardController
{
    private BookingService $bookingService;
    private PaymentService $paymentService;
    private UserService $userService;
    private LoggerInterface $logger;

    public function __construct(
        BookingService $bookingService,
        PaymentService $paymentService,
        UserService $userService,
        LoggerInterface $logger
    ) {
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
        $this->userService = $userService;
        $this->logger = $logger;
    }

    /**
     * Render the admin dashboard view.
     */
    public function index(): void
    {
        try {
            $metrics = [
                'total_users' => $this->userService->getTotalUsers(),
                'active_users' => $this->userService->getActiveUsers(),
                'total_bookings' => $this->bookingService->getTotalBookings(),
                'completed_bookings' => $this->bookingService->getCompletedBookings(),
                'canceled_bookings' => $this->bookingService->getCanceledBookings(),
                'total_revenue' => $this->paymentService->getTotalRevenue(),
                'total_refunds' => $this->paymentService->getTotalRefunds(),
                'net_revenue' => $this->paymentService->getNetRevenue(),
            ];

            // Fetch data for graphs
            $graphData = [
                'booking_trends' => $this->bookingService->getMonthlyBookingTrends(),
                'revenue_trends' => $this->paymentService->getMonthlyRevenueTrends(),
            ];

            view('admin/dashboard', ['metrics' => $metrics, 'graphData' => $graphData]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load admin dashboard', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Error loading the dashboard. Please try again later.';
        }
    }

    /**
     * Get data for admin dashboard metrics and graphs (API).
     */
    public function getDashboardData(): void
    {
        requireAuth(); // ensure admin authentication is in place
        try {
            $metrics = [
                'total_users' => $this->userService->getTotalUsers(),
                'active_users' => $this->userService->getActiveUsers(),
                'total_bookings' => $this->bookingService->getTotalBookings(),
                'completed_bookings' => $this->bookingService->getCompletedBookings(),
                'canceled_bookings' => $this->bookingService->getCanceledBookings(),
                'total_revenue' => $this->paymentService->getTotalRevenue(),
                'total_refunds' => $this->paymentService->getTotalRefunds(),
                'net_revenue' => $this->paymentService->getNetRevenue(),
            ];

            $graphData = [
                'booking_trends' => $this->bookingService->getMonthlyBookingTrends(),
                'revenue_trends' => $this->paymentService->getMonthlyRevenueTrends(),
            ];

            echo json_encode([
                'status' => 'success',
                'metrics' => $metrics,
                'graph_data' => $graphData,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch admin dashboard data', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch dashboard data']);
        }
    }
}
