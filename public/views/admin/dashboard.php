<?php
/**
 * Admin Dashboard - Panel Administratora
 * Main dashboard view for CarFuse administrators
 */

// Set layout variables for base template
$pageTitle = "CarFuse - Panel Administratora";
$metaDescription = "Panel administratora CarFuse - zarządzaj systemem, użytkownikami i rezerwacjami.";

// Start output buffering to capture content for the base template
ob_start();
?>

<div class="container mx-auto px-4 py-8" 
     x-data="authState()" 
     x-init="$nextTick(() => { 
        if(!isAuthenticated || userRole !== 'admin') {
            window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
        }
     })">
    
    <!-- Authentication Check with JS -->
    <template x-if="isAuthenticated && userRole === 'admin'">
        <!-- Dashboard Header -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Panel Administratora</h1>
                    <p class="text-gray-600">Witaj, <span x-text="username || 'Administrator'"></span>. Zarządzaj systemem, użytkownikami i rezerwacjami.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="/admin/settings" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-cog mr-2"></i> Ustawienia Systemu
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div id="users-stats" class="bg-white rounded-lg shadow p-6"
                 hx-get="/admin/api/stats/users" 
                 hx-trigger="load" 
                 hx-swap="innerHTML"
                 hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                <div class="flex justify-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
            </div>
            
            <div id="vehicles-stats" class="bg-white rounded-lg shadow p-6"
                 hx-get="/admin/api/stats/vehicles" 
                 hx-trigger="load" 
                 hx-swap="innerHTML"
                 hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                <div class="flex justify-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
            </div>
            
            <div id="bookings-stats" class="bg-white rounded-lg shadow p-6"
                 hx-get="/admin/api/stats/bookings" 
                 hx-trigger="load" 
                 hx-swap="innerHTML"
                 hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                <div class="flex justify-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
            </div>
            
            <div id="revenue-stats" class="bg-white rounded-lg shadow p-6"
                 hx-get="/admin/api/stats/revenue" 
                 hx-trigger="load" 
                 hx-swap="innerHTML"
                 hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                <div class="flex justify-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities and Alerts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Recent Activities -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-medium text-lg">Ostatnie Aktywności</h3>
                </div>
                <div id="recent-activities" class="p-6"
                     hx-get="/admin/api/activities" 
                     hx-trigger="load" 
                     hx-swap="innerHTML"
                     hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                    <div class="flex justify-center py-4">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>
                </div>
            </div>
            
            <!-- System Alerts -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-medium text-lg">Alerty Systemowe</h3>
                </div>
                <div id="system-alerts" class="p-6"
                     hx-get="/admin/api/alerts" 
                     hx-trigger="load" 
                     hx-swap="innerHTML"
                     hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                    <div class="flex justify-center py-4">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Action Buttons -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="/admin/users" class="bg-white hover:bg-gray-50 rounded-lg shadow p-4 text-center">
                <i class="fas fa-users text-blue-500 text-2xl mb-2"></i>
                <p class="font-medium">Zarządzaj Użytkownikami</p>
            </a>
            <a href="/admin/vehicles" class="bg-white hover:bg-gray-50 rounded-lg shadow p-4 text-center">
                <i class="fas fa-car text-green-500 text-2xl mb-2"></i>
                <p class="font-medium">Zarządzaj Pojazdami</p>
            </a>
            <a href="/admin/bookings" class="bg-white hover:bg-gray-50 rounded-lg shadow p-4 text-center">
                <i class="fas fa-calendar-alt text-purple-500 text-2xl mb-2"></i>
                <p class="font-medium">Zarządzaj Rezerwacjami</p>
            </a>
            <a href="/admin/reports" class="bg-white hover:bg-gray-50 rounded-lg shadow p-4 text-center">
                <i class="fas fa-chart-line text-red-500 text-2xl mb-2"></i>
                <p class="font-medium">Generuj Raporty</p>
            </a>
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
                        Ta strona wymaga JavaScript do prawidłowego działania. Proszę włączyć JavaScript w przeglądarce.
                    </p>
                </div>
            </div>
        </div>
        
        <?php
        // Server-side authentication check for no-JS browsers
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            echo '<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">Dostęp tylko dla administratorów. Przekierowywanie...</p></div>';
            echo '<script>window.location.href = "/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '";</script>';
        } else {
            // Basic admin info display for no-JS browsers
            $userName = $_SESSION['user_name'] ?? 'Administrator';
            echo '<div class="bg-white shadow-md rounded-lg p-6 mb-6">';
            echo '<h1 class="text-2xl font-bold text-gray-800">Panel Administratora</h1>';
            echo '<p class="text-gray-600">Witaj, ' . htmlspecialchars($userName) . '. Zarządzaj systemem, użytkownikami i rezerwacjami.</p>';
            echo '</div>';
            
            // Basic links for no-JS browsers
            echo '<div class="bg-white shadow-md rounded-lg p-6 mb-6">';
            echo '<h2 class="text-lg font-medium mb-4">Szybkie akcje:</h2>';
            echo '<ul class="space-y-2">';
            echo '<li><a href="/admin/users" class="text-blue-600 hover:underline">Zarządzaj Użytkownikami</a></li>';
            echo '<li><a href="/admin/vehicles" class="text-blue-600 hover:underline">Zarządzaj Pojazdami</a></li>';
            echo '<li><a href="/admin/bookings" class="text-blue-600 hover:underline">Zarządzaj Rezerwacjami</a></li>';
            echo '<li><a href="/admin/settings" class="text-blue-600 hover:underline">Ustawienia Systemu</a></li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
    </noscript>
</div>

<?php
// Get buffered content
$content = ob_get_clean();

// Include base layout
include BASE_PATH . '/public/views/layouts/base.php';
?>