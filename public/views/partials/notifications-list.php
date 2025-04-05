<?php
/**
 * Consolidated Notifications List Component
 * Works for both admin and user contexts
 */

// Detect context - check if we're in admin context
$isAdmin = isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'manager');
$userId = $_SESSION['user_id'] ?? null;

// Set appropriate endpoints based on context
$endpointBase = $isAdmin ? '/admin' : '/user';
$notificationsEndpoint = $endpointBase . '/notifications';
$markReadEndpoint = $endpointBase . '/notifications/' . ($isAdmin ? 'mark-read' : 'mark-as-read');
$markAllReadEndpoint = $endpointBase . '/notifications/mark-all-read';
$deleteEndpoint = $endpointBase . '/notifications/delete';
$containerId = $isAdmin ? 'admin-notifications-container' : 'notifications-container';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Component title
$title = $isAdmin ? "Powiadomienia Systemowe" : "Twoje Powiadomienia";

// Empty state message
$emptyStateMessage = $isAdmin 
    ? "Nie ma żadnych powiadomień systemowych do wyświetlenia."
    : "Nie masz żadnych powiadomień do wyświetlenia.";
?>

<div 
    x-data="notificationsPanel(<?= $isAdmin ? 'true' : 'false' ?>)" 
    @notification-received.window="addNotification($event.detail)"
    class="bg-white rounded-lg shadow divide-y divide-gray-200">

    <div class="p-4 flex items-center justify-between bg-gray-50">
        <h3 class="text-lg font-medium text-gray-700"><?= $title ?></h3>
        
        <!-- Mark all as read button -->
        <button 
            x-show="notificationCount > 0"
            data-mark-all-read
            data-target="#<?= $containerId ?>"
            class="text-sm text-blue-600 hover:text-blue-800">
            Oznacz wszystkie jako przeczytane
            <div class="htmx-indicator inline-flex items-center">
                <svg class="htmx-indicator-spinner h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </button>
    </div>
    
    <!-- Notifications content will be loaded here -->
    <div id="<?= $containerId ?>" class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
        <div class="htmx-indicator flex justify-center py-4">
            <svg class="htmx-indicator-spinner h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2 text-sm text-gray-500">Ładowanie powiadomień...</span>
        </div>
    </div>
</div>
