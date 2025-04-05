<div class="booking-table-component" 
     x-data="{ 
        showRescheduleModal: false,
        currentBookingId: null,
        pickupDate: '',
        dropoffDate: '',
        showDetails: {}
     }">
     
    <!-- Booking table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nr
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Pojazd
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Data rozpoczęcia
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Data zakończenia
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Cena całkowita
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Akcje
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <p class="text-base font-medium">Brak rezerwacji</p>
                            <p class="text-sm">Nie znaleziono żadnych rezerwacji spełniających kryteria wyszukiwania.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- Booking ID -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?= htmlspecialchars($booking['id']) ?>
                            </td>
                            
                            <!-- Vehicle info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if (!empty($booking['vehicle_image'])): ?>
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" src="<?= htmlspecialchars($booking['vehicle_image']) ?>" alt="<?= htmlspecialchars($booking['vehicle_name']) ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($booking['vehicle_name'] ?? 'Niezdefiniowany pojazd') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($booking['vehicle_model'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Start date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= date('d.m.Y', strtotime($booking['pickup_date'])) ?></div>
                                <div class="text-sm text-gray-500"><?= date('H:i', strtotime($booking['pickup_date'])) ?></div>
                            </td>
                            
                            <!-- End date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= date('d.m.Y', strtotime($booking['dropoff_date'])) ?></div>
                                <div class="text-sm text-gray-500"><?= date('H:i', strtotime($booking['dropoff_date'])) ?></div>
                            </td>
                            
                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($booking['status'] === 'pending'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Oczekująca
                                    </span>
                                <?php elseif ($booking['status'] === 'confirmed'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Potwierdzona
                                    </span>
                                <?php elseif ($booking['status'] === 'active'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Aktywna
                                    </span>
                                <?php elseif ($booking['status'] === 'completed'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Zakończona
                                    </span>
                                <?php elseif ($booking['status'] === 'canceled'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Anulowana
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <?= htmlspecialchars($booking['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Total price -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= number_format($booking['total_price'], 2, ',', ' ') ?> zł
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="/booking/<?= $booking['id'] ?>" class="text-blue-600 hover:text-blue-900">
                                        Podgląd
                                    </a>
                                    
                                    <?php if (in_array($booking['status'], ['pending', 'confirmed', 'active'])): ?>
                                        <button
                                            @click="currentBookingId = <?= $booking['id'] ?>; showRescheduleModal = true; pickupDate = '<?= date('Y-m-d', strtotime($booking['pickup_date'])) ?>'; dropoffDate = '<?= date('Y-m-d', strtotime($booking['dropoff_date'])) ?>'"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            Zmień termin
                                        </button>
                                        
                                        <button
                                            data-booking-cancel
                                            data-booking-id="<?= $booking['id'] ?>"
                                            data-target="#booking-list"
                                            class="text-red-600 hover:text-red-900">
                                            Anuluj
                                            <div class="htmx-indicator inline-flex items-center">
                                                <svg class="htmx-indicator-spinner h-4 w-4 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Expandable details section -->
                        <tr id="booking-details-<?= $booking['id'] ?>" 
                            x-show="showDetails[<?= $booking['id'] ?>]" 
                            x-cloak 
                            class="bg-gray-50">
                            <td colspan="7" class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-2">Miejsce odbioru</h4>
                                        <p class="text-sm text-gray-900"><?= htmlspecialchars($booking['pickup_location']) ?></p>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500 mb-2">Miejsce zwrotu</h4>
                                        <p class="text-sm text-gray-900"><?= htmlspecialchars($booking['dropoff_location']) ?></p>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-500 mb-2">Historia rezerwacji</h4>
                                    <div hx-get="/booking/<?= $booking['id'] ?>/logs" 
                                         hx-trigger="revealed once"
                                         hx-indicator=".htmx-indicator">
                                        <div class="htmx-indicator flex items-center">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span class="text-sm text-gray-500">Ładowanie historii...</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Reschedule Modal -->
    <div x-show="showRescheduleModal" 
         x-cloak
         class="fixed z-10 inset-0 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showRescheduleModal = false"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form hx-post="/booking/" :hx-post="'/booking/' + currentBookingId + '/reschedule'" hx-swap="outerHTML" class="space-y-4">
                    <div>
                        <label for="pickup_date" class="block text-sm font-medium text-gray-700">Nowa data odbioru</label>
                        <input type="date" name="pickup_date" id="pickup_date" x-model="pickupDate" required
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="dropoff_date" class="block text-sm font-medium text-gray-700">Nowa data zwrotu</label>
                        <input type="date" name="dropoff_date" id="dropoff_date" x-model="dropoffDate" required
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
                        <button type="button" @click="showRescheduleModal = false" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                            Anuluj
                        </button>
                        <button type="submit" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:text-sm">
                            Potwierdź zmianę
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .htmx-indicator { display: none; }
        .htmx-request .htmx-indicator { display: flex; }
        .htmx-request.htmx-indicator { display: flex; }
    </style>
</div>
