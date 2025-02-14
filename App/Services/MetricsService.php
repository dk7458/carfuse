<?php

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;

class MetricsService
{
    public function getDashboardMetrics(): array
    {
        $metrics = [
            'total_users'         => User::count(),
            'active_users'        => User::where('active', true)->count(),
            'total_bookings'      => Booking::count(),
            'completed_bookings'  => Booking::where('status', 'completed')->count(),
            'canceled_bookings'   => Booking::where('status', 'canceled')->count(),
            'total_revenue'       => Payment::where('status', 'completed')->sum('amount'),
            'total_refunds'       => Payment::where('status', 'completed')
                                           ->where('type', 'refund')
                                           ->sum('amount'),
        ];
        $metrics['net_revenue'] = $metrics['total_revenue'] - $metrics['total_refunds'];

        return $metrics;
    }
}
