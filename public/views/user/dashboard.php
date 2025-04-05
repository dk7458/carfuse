<?php
/**
 * User Dashboard - Panel Użytkownika
 * Main dashboard view for CarFuse users
 * 
 * Connected to DashboardController methods:
 * - index() - Main view
 * - getUserStatistics() - For statistics panel
 * - getUserBookings() - For recent bookings
 * - getUserNotifications() - For notifications panel
 */

// Set layout variables for base template
$pageTitle = "CarFuse - Panel Użytkownika";
$metaDescription = "Panel użytkownika CarFuse - zarządzaj swoimi rezerwacjami i sprawdź dostępne pojazdy.";

// Helper function to format currency
function formatCurrency($amount) {
    return number_format($amount, 2, ',', ' ') . ' zł';
}

// Start output buffering to capture content for the base template
ob_start();
?>

<div class="container mx-auto px-4 py-8" 
     x-data="authState()" 
     x-init="$nextTick(() => { 
        if(!isAuthenticated) {
            window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
        }
     })">
    <!-- Authentication Check with JS -->
    <template x-if="isAuthenticated">
        <!-- Dashboard Header -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Witaj, <span x-text="username || 'Użytkowniku'"></span> 👋</h1>
                    <p class="text-gray-600">Zarządzaj swoimi rezerwacjami i sprawdź dostępne pojazdy w jednym miejscu.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="/vehicles/browse" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-car mr-2"></i> Przeglądaj pojazdy
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="mb-6" id="user-statistics" 
             hx-get="/dashboard/statistics" 
             hx-trigger="load" 
             hx-indicator=".htmx-indicator"
             hx-swap="innerHTML"
             hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
            <div class="flex justify-center htmx-indicator">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
        </div>
    </template>

    <!-- No JavaScript fallback -->
    <noscript>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Ta strona wymaga JavaScript do prawidłowego działania. Proszę włączyć JavaScript w przeglądarce lub użyć innej przeglądarki.
                    </p>
                    <p class="mt-2">
                        <a href="/auth/login" class="text-sm font-medium text-yellow-700 underline hover:text-yellow-600">
                            Przejdź do strony logowania
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <?php
        // Server-side authentication check for no-JS browsers
        if (!isset($_SESSION['user_id'])) {
            echo '<script>window.location.href = "/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '";</script>';
            echo '<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">Musisz być zalogowany, aby zobaczyć tę stronę. Przekierowywanie...</p></div>';
            // Include a basic version of the login form or a link to the login page
        } else {
            // Basic user info for no-JS browsers
            $userName = $_SESSION['user_name'] ?? 'Użytkowniku';
            echo '<div class="bg-white shadow-md rounded-lg p-6 mb-6">';
            echo '<h1 class="text-2xl font-bold text-gray-800">Witaj, ' . htmlspecialchars($userName) . ' 👋</h1>';
            echo '<p class="text-gray-600">Zarządzaj swoimi rezerwacjami i sprawdź dostępne pojazdy w jednym miejscu.</p>';
            echo '</div>';
            
            // Basic stats container for no-JS browsers
            echo '<div class="mb-6 bg-white shadow-md rounded-lg p-6">';
            echo '<p class="text-center text-gray-500">Ładowanie statystyk...</p>';
            echo '</div>';
        }
        ?>
    </noscript>
</div>

<script>
    // Update notification count when notifications are loaded
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if (event.detail.target.id === 'notifications-list') {
            // Count unread notifications
            const unreadCount = document.querySelectorAll('#notifications-list .unread-notification').length;
            document.getElementById('notification-count').innerText = unreadCount;
            
            // If no unread notifications, remove the red background
            if (unreadCount === 0) {
                document.getElementById('notification-count').classList.remove('bg-red-500');
                document.getElementById('notification-count').classList.add('bg-gray-300');
            } else {
                document.getElementById('notification-count').classList.add('bg-red-500');
                document.getElementById('notification-count').classList.remove('bg-gray-300');
            }
        }
        
        // Handle empty bookings
        if (event.detail.target.id === 'recent-bookings-content') {
            const hasBookings = document.querySelector('#recent-bookings-content .booking-item');
            if (!hasBookings) {
                document.querySelector('#recent-bookings-content').innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-5xl mb-4">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3 class="text-xl font-medium text-gray-700 mb-1">Brak rezerwacji</h3>
                        <p class="text-gray-500 mb-4">Nie masz jeszcze żadnych rezerwacji</p>
                        <a href="/vehicles/browse" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md">
                            <i class="fas fa-car mr-2"></i> Zarezerwuj pojazd
                        </a>
                    </div>
                `;
            }
        }
    });
    
    // Handle HTMX error responses
    document.body.addEventListener('htmx:responseError', function(event) {
        const targetId = event.detail.target.id;
        
        if (targetId === 'user-statistics') {
            event.detail.target.innerHTML = `
                <div class="bg-red-50 p-4 rounded-lg text-red-800 text-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> Nie udało się załadować statystyk. 
                    <button hx-get="/dashboard/statistics" hx-target="#user-statistics" class="underline ml-2">
                        Spróbuj ponownie
                    </button>
                </div>
            `;
        }
        
        if (targetId === 'recent-bookings-content') {
            event.detail.target.innerHTML = `
                <div class="bg-red-50 p-4 rounded-lg text-red-800 text-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> Nie udało się załadować rezerwacji. 
                    <button hx-get="/dashboard/bookings" hx-target="#recent-bookings-content" class="underline ml-2">
                        Spróbuj ponownie
                    </button>
                </div>
            `;
        }
        
        if (targetId === 'notifications-list') {
            event.detail.target.innerHTML = `
                <div class="bg-red-50 p-4 rounded-lg text-red-800 text-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> Nie udało się załadować powiadomień. 
                    <button hx-get="/dashboard/notifications" hx-target="#notifications-list" class="underline ml-2">
                        Spróbuj ponownie
                    </button>
                </div>
            `;
        }
    });
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include base layout
include BASE_PATH . '/public/views/layouts/base.php';
?>
