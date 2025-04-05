<?php
// Set page metadata
$pageTitle = "Moje Rezerwacje";
$metaDescription = "Zarządzaj swoimi rezerwacjami samochodów w CarFuse";

// Start output buffering to capture content for the base template
ob_start();
?>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8"
     x-data="authState()"
     x-init="$nextTick(() => { 
        if(!isAuthenticated) {
            window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
        }
     })">
    
    <template x-if="isAuthenticated">
        <div class="max-w-5xl mx-auto">
            <!-- Include user statistics at the top -->
            <div class="mb-8" id="user-statistics" 
                 hx-get="/dashboard/statistics" 
                 hx-trigger="load" 
                 hx-indicator=".htmx-indicator"
                 hx-swap="innerHTML"
                 hx-headers='{"Authorization": "Bearer "+window.AuthHelper.getToken()}'>
                <div class="flex justify-center htmx-indicator">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
            </div>
            
            <!-- Main bookings management component -->
            <div class="bg-white rounded-lg shadow-md p-6" 
                 x-data="{ 
                    currentPage: 0,
                    perPage: 5,
                    status: 'all',
                    loading: false,
                    hasMorePages: true,
                    showModal: false,
                    modalType: '',
                    actionBookingId: null
                 }" x-init="loadBookings()">
        
                <!-- Header section -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Moje Rezerwacje</h2>
                        <p class="text-gray-500 text-sm mt-1">Zarządzaj swoimi rezerwacjami samochodów</p>
                    </div>
                    
                    <div class="mt-4 md:mt-0">
                        <a href="/booking/new" 
                           class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition flex items-center">
                            <i class="fas fa-plus mr-2"></i> Nowa rezerwacja
                        </a>
                    </div>
                </div>
                
                <!-- Bookings will be loaded here via JS -->
                <div id="bookings-container">
                    <div class="flex justify-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    </div>
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
        } else {
            // Basic bookings interface for no-JS browsers
            echo '<div class="max-w-5xl mx-auto">';
            echo '<div class="bg-white rounded-lg shadow-md p-6">';
            echo '<h2 class="text-2xl font-bold text-gray-800">Moje Rezerwacje</h2>';
            echo '<p class="text-gray-500 text-sm mt-1">Zarządzaj swoimi rezerwacjami samochodów</p>';
            echo '<p class="mt-4 text-gray-600">Aby w pełni korzystać z funkcjonalności rezerwacji, włącz JavaScript w przeglądarce.</p>';
            echo '<p class="mt-2"><a href="/booking/list" class="text-blue-600 hover:text-blue-800">Zobacz podstawową listę rezerwacji</a></p>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </noscript>
</div>

<script>
// Booking management functions
function loadBookings() {
    const bookingsContainer = document.getElementById('bookings-container');
    if (!bookingsContainer) return;
    
    // Show loading state
    this.loading = true;
    
    // Prepare headers with authentication token
    const headers = {
        'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
        'Content-Type': 'application/json'
    };
    
    // Fetch bookings from API
    fetch(`/api/user/bookings?page=${this.currentPage}&status=${this.status}&per_page=${this.perPage}`, {
        headers: headers
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update component state with booking data
            this.hasMorePages = data.data.has_more_pages;
            
            // Render bookings
            bookingsContainer.innerHTML = data.data.bookings.length > 0 
                ? renderBookingsList(data.data.bookings)
                : renderEmptyState();
        } else {
            throw new Error(data.message || 'Failed to load bookings');
        }
    })
    .catch(error => {
        console.error('Error loading bookings:', error);
        bookingsContainer.innerHTML = `
            <div class="text-center py-4 text-red-500">
                <p>Wystąpił błąd podczas ładowania rezerwacji.</p>
                <button class="mt-2 text-blue-600 hover:text-blue-800" @click="loadBookings()">
                    Spróbuj ponownie
                </button>
            </div>
        `;
    })
    .finally(() => {
        this.loading = false;
    });
}

function renderBookingsList(bookings) {
    // Implement a function to render the bookings list UI
    return `
        <div class="space-y-4">
            ${bookings.map(booking => `
                <div class="border rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-lg">${booking.vehicle_name}</h3>
                            <p class="text-sm text-gray-600">${booking.pickup_date} - ${booking.dropoff_date}</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full ${getStatusClass(booking.status)}">
                            ${getStatusLabel(booking.status)}
                        </span>
                    </div>
                    <!-- Additional booking details -->
                </div>
            `).join('')}
        </div>
        
        <!-- Pagination controls -->
        <div class="mt-6 flex justify-between items-center">
            <button class="text-gray-600 hover:text-gray-800" 
                    @click="if(currentPage > 0) { currentPage--; loadBookings(); }"
                    :disabled="currentPage === 0"
                    :class="{'opacity-50 cursor-not-allowed': currentPage === 0}">
                <i class="fas fa-chevron-left mr-1"></i> Poprzednia
            </button>
            <button class="text-gray-600 hover:text-gray-800" 
                    @click="currentPage++; loadBookings();"
                    :disabled="!hasMorePages"
                    :class="{'opacity-50 cursor-not-allowed': !hasMorePages}">
                Następna <i class="fas fa-chevron-right ml-1"></i>
            </button>
        </div>
    `;
}

function renderEmptyState() {
    return `
        <div class="text-center py-8">
            <div class="rounded-full bg-gray-100 h-16 w-16 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-calendar-times text-gray-400 text-xl"></i>
            </div>
            <h3 class="text-gray-800 font-medium mb-1">Nie masz żadnych rezerwacji</h3>
            <p class="text-gray-500 mb-4">Zarezerwuj swój pierwszy samochód już dziś!</p>
            <a href="/vehicles" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition inline-block">
                <i class="fas fa-car mr-2"></i> Przeglądaj dostępne pojazdy
            </a>
        </div>
    `;
}

function getStatusClass(status) {
    const statusClasses = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'confirmed': 'bg-blue-100 text-blue-800',
        'active': 'bg-green-100 text-green-800',
        'completed': 'bg-purple-100 text-purple-800',
        'canceled': 'bg-red-100 text-red-800'
    };
    
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
}

function getStatusLabel(status) {
    const statusLabels = {
        'pending': 'Oczekująca',
        'confirmed': 'Potwierdzona',
        'active': 'Aktywna',
        'completed': 'Zakończona',
        'canceled': 'Anulowana'
    };
    
    return statusLabels[status] || status;
}
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the base layout with the content
include BASE_PATH . '/public/views/layouts/base.php';
?>
