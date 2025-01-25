<?php

use App\Services\NotificationService;
use App\Models\User;

$notificationService = new NotificationService();
$user = User::find(1);

// Send email notification
$emailData = [
    'subject' => 'Welcome to our platform',
    'name' => $user->name
];
$notificationService->sendEmailNotification($user, 'welcome', $emailData);

// Send SMS notification
$smsData = [
    'code' => '123456'
];
$notificationService->sendSmsNotification($user, 'verification', $smsData);

// Send push notification
$pushData = [
    'title' => 'New Message',
    'body' => 'You have a new message'
];
$notificationService->sendPushNotification($user, 'new_message', $pushData);

// Send batch notifications
$users = User::where('active', true)->get();
$batchData = [
    'title' => 'System Maintenance',
    'message' => 'System will be down for maintenance'
];
$results = $notificationService->sendBatchNotifications(
    $users,
    'email',
    'maintenance_notice',
    $batchData
);

echo "Batch results: {$results['success']} successful, {$results['failed']} failed\n";
