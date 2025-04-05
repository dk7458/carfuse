<?php
/**
 * Admin Vehicles Management Page
 * Vehicle management interface for CarFuse administrators
 */

// Set page title and meta description
$pageTitle = "CarFuse - Zarządzanie Pojazdami";
$metaDescription = "Panel zarządzania pojazdami systemu CarFuse";

// Start output buffering to capture the main content
ob_start();
?>

<div class="container mx-auto px-4 py-8" 
     x-data="Object.assign(vehicleManagement(), authState())"
     x-init="$nextTick(() => {
        // Verify admin authorization
        if (!isAuthenticated || userRole !== 'admin') {
            window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
        } else {
            init();
        }
     })">
    
    <template x-if="isAuthenticated && userRole === 'admin'">
        <!-- Page Header -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Zarządzanie Pojazdami</h1>
                    <p class="text-gray-600">Dodawaj, edytuj i zarządzaj flotą pojazdów.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <button @click="openNewVehicleModal()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-plus mr-2"></i> Dodaj Pojazd
                    </button>
                </div>
            </div>
        </div>

        <!-- Vehicle List -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <!-- ...existing vehicle management content... -->
            <div id="vehicles-list" class="p-6"
                 hx-get="/admin/api/vehicles" 
                 hx-trigger="load" 
                 hx-swap="innerHTML"
                 hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                <div class="flex justify-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
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
                        Ta strona wymaga JavaScript do prawidłowego działania. Proszę włączyć JavaScript w przeglądarce.
                    </p>
                </div>
            </div>
        </div>

        <?php
        // Server-side authentication fallback for no-JS browsers
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            echo '<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">Dostęp tylko dla administratorów. Przekierowywanie...</p></div>';
            echo '<script>window.location.href = "/auth/login?redirect=' . urlencode($_SERVER['REQUEST_URI']) . '";</script>';
        } else {
            echo '<div class="bg-white shadow-md rounded-lg p-6 mb-6">';
            echo '<h1 class="text-2xl font-bold text-gray-800">Zarządzanie Pojazdami</h1>';
            echo '<p class="text-gray-600">Panel dostępny tylko z włączonym JavaScript.</p>';
            echo '<p class="mt-4"><a href="/admin/dashboard" class="text-blue-600 hover:underline">« Powrót do panelu administratora</a></p>';
            echo '</div>';
        }
        ?>
    </noscript>
</div>

<!-- Vehicle management Alpine component -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('vehicleManagement', () => ({
        vehicles: [],
        loading: false,
        // ...other vehicle management data...
        
        init() {
            // Initial loading handled by HTMX
        },
        
        openNewVehicleModal() {
            // Implementation for adding new vehicles
        },
        
        deleteVehicle(id) {
            if (!confirm('Czy na pewno chcesz usunąć ten pojazd?')) return;
            
            fetch(`/admin/api/vehicles/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Refresh the vehicle list
                    htmx.trigger('#vehicles-list', 'load');
                } else {
                    alert('Nie udało się usunąć pojazdu: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting vehicle:', error);
            });
        }
    }));
});
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include base layout
include BASE_PATH . '/public/views/layouts/base.php';
?>
