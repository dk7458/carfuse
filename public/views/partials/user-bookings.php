<div class="bg-white rounded-lg shadow-md p-6" 
     x-data="{ 
        currentPage: 0,
        perPage: 5,
        status: 'all',
        loading: false,
        hasMorePages: true
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
                <svg class="w-5 h-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Zarezerwuj samochód
            </a>
        </div>
    </div>
    
    <!-- Filter section -->
    <div class="flex flex-wrap gap-2 mb-5">
        <button @click="status = 'all'; currentPage = 0; loadBookings()"
                class="px-3 py-1.5 rounded-md text-sm transition"
                :class="status === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
            Wszystkie
        </button>
        <button @click="status = 'active'; currentPage = 0; loadBookings()"
                class="px-3 py-1.5 rounded-md text-sm transition"
                :class="status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
            Aktywne
        </button>
        <button @click="status = 'completed'; currentPage = 0; loadBookings()"
                class="px-3 py-1.5 rounded-md text-sm transition"
                :class="status === 'completed' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
            Zakończone
        </button>
        <button @click="status = 'canceled'; currentPage = 0; loadBookings()"
                class="px-3 py-1.5 rounded-md text-sm transition"
                :class="status === 'canceled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
            Anulowane
        </button>
        <button @click="status = 'pending'; currentPage = 0; loadBookings()"
                class="px-3 py-1.5 rounded-md text-sm transition"
                :class="status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
            Oczekujące
        </button>
    </div>

    <!-- Booking list -->
    <div id="bookings-container"
         hx-get="/booking/list" 
         hx-trigger="load"
         hx-vals="js:{page: 0, per_page: 5, status: 'all'}"
         hx-indicator="#booking-spinner">
        <!-- Initial loading state -->
        <div class="flex justify-center py-8" id="booking-spinner">
            <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
    
    <!-- Load more button -->
    <div class="text-center mt-5" x-show="hasMorePages">
        <button @click="currentPage++; loadBookings(true)" 
                class="bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-md transition"
                x-bind:disabled="loading">
            <span x-show="!loading">Pokaż więcej rezerwacji</span>
            <span x-show="loading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Ładowanie...
            </span>
        </button>
    </div>
    
    <!-- No bookings message -->
    <div id="no-bookings-message" class="hidden text-center py-10">
        <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-700 mt-4">Brak rezerwacji</h3>
        <p class="text-gray-500 mt-2">Nie masz jeszcze żadnych rezerwacji w tej kategorii.</p>
        <a href="/booking/new" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition">
            Zarezerwuj swój pierwszy samochód
        </a>
    </div>
</div>

<script>
function loadBookings(append = false) {
    this.loading = true;
    
    const url = '/booking/list';
    const params = new URLSearchParams({
        page: this.currentPage,
        per_page: this.perPage,
        status: this.status
    });
    
    htmx.ajax('GET', `${url}?${params.toString()}`, {
        target: append ? '#bookings-container-append' : '#bookings-container',
        swap: append ? 'beforeend' : 'innerHTML',
        headers: {
            'HX-Request': 'true'
        }
    }).then(() => {
        this.loading = false;
        
        // Check if we have more pages
        const bookingsCount = document.querySelectorAll('#bookings-container .booking-item').length;
        if (bookingsCount < (this.currentPage + 1) * this.perPage) {
            this.hasMorePages = false;
        }
        
        // Show no bookings message if needed
        if (bookingsCount === 0 && this.currentPage === 0) {
            document.getElementById('no-bookings-message').classList.remove('hidden');
        } else {
            document.getElementById('no-bookings-message').classList.add('hidden');
        }
        
        // Create append container if needed
        if (append && !document.getElementById('bookings-container-append')) {
            const appendContainer = document.createElement('div');
            appendContainer.id = 'bookings-container-append';
            document.getElementById('bookings-container').appendChild(appendContainer);
        }
    });
}
</script>

<?php if (empty($bookings)): ?>
<script>document.getElementById('no-bookings-message').classList.remove('hidden');</script>
<?php else: ?>
<div class="space-y-4">
    <?php foreach ($bookings as $booking): ?>
    <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
        <div class="flex flex-wrap md:flex-nowrap">
            <!-- Vehicle Image -->
            <div class="w-full md:w-1/4">
                <img src="<?= htmlspecialchars($booking->vehicle_image ?? '/images/cars/default.jpg') ?>" 
                     alt="<?= htmlspecialchars($booking->vehicle_name ?? 'Vehicle') ?>" 
                     class="w-full h-32 md:h-full object-cover">
            </div>
            
            <!-- Booking Details -->
            <div class="w-full md:w-3/4 p-4">
                <div class="flex flex-wrap justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($booking->vehicle_name ?? 'Pojazd') ?></h3>
                        <div class="flex items-center text-sm text-gray-600 mt-1">
                            <i class="fas fa-calendar mr-1"></i>
                            <?= (new DateTime($booking->start_date))->format('d.m.Y H:i') ?>
                            <i class="fas fa-arrow-right mx-2"></i>
                            <?= (new DateTime($booking->end_date))->format('d.m.Y H:i') ?>
                        </div>
                        <div class="flex items-center text-sm text-gray-600 mt-1">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?= htmlspecialchars($booking->pickup_location ?? 'Lokalizacja odbioru') ?>
                        </div>
                    </div>
                    
                    <!-- Status Badge and Actions -->
                    <div class="mt-2 md:mt-0">
                        <?php
                        $statusClass = '';
                        switch ($booking->status) {
                            case 'completed':
                                $statusClass = 'bg-green-100 text-green-800';
                                $statusText = 'Ukończona';
                                break;
                            case 'canceled':
                                $statusClass = 'bg-red-100 text-red-800';
                                $statusText = 'Anulowana';
                                break;
                            case 'pending':
                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                $statusText = 'Oczekująca';
                                break;
                            default:
                                $statusClass = 'bg-blue-100 text-blue-800';
                                $statusText = 'Aktywna';
                        }
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                            <?= $statusText ?>
                        </span>
                        
                        <div class="mt-3 flex space-x-2">
                            <a href="/bookings/<?= $booking->id ?>" class="text-blue-600 hover:text-blue-900 flex items-center text-sm">
                                <i class="fas fa-eye mr-1"></i> Szczegóły
                            </a>
                            
                            <?php if ($booking->status === 'active' || $booking->status === 'pending'): ?>
                            <a href="/bookings/<?= $booking->id ?>/cancel" class="text-red-600 hover:text-red-900 flex items-center text-sm">
                                <i class="fas fa-times mr-1"></i> Anuluj
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($booking->status === 'active' || $booking->status === 'pending'): ?>
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">
                            <i class="fas fa-money-bill-wave mr-1"></i> Całkowity koszt:
                            <span class="font-semibold"><?= formatCurrency($booking->total_price ?? 0) ?></span>
                        </span>
                        
                        <?php if ($booking->status === 'pending'): ?>
                        <a href="/payments/<?= $booking->id ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm">
                            <i class="fas fa-credit-card mr-1"></i> Zapłać teraz
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
