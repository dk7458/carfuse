<div class="booking-item border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow mb-4" 
     x-data="bookingCard({
        id: <?= $booking->id ?>,
        status: '<?= $booking->status ?>', 
        expanded: false
     })">
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
                    
                    <button @click="toggleExpand()" class="mt-2 text-blue-600 text-sm flex items-center">
                        <span x-text="expanded ? 'Zwiń szczegóły' : 'Zobacz szczegóły'"></span>
                        <svg class="w-4 h-4 ml-1 transition-transform" :class="expanded ? 'transform rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
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
                        <button type="button" 
                                onclick="openModal('cancel', <?= $booking->id ?>)" 
                                class="text-red-600 hover:text-red-900 flex items-center text-sm bg-transparent border-0 p-0 cursor-pointer">
                            <i class="fas fa-times mr-1"></i> Anuluj
                        </button>
                        
                        <button type="button" 
                                onclick="openModal('reschedule', <?= $booking->id ?>)"
                                class="text-yellow-600 hover:text-yellow-900 flex items-center text-sm bg-transparent border-0 p-0 cursor-pointer">
                            <i class="fas fa-calendar-alt mr-1"></i> Zmień termin
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Expandable Details -->
            <div x-show="expanded" x-collapse x-cloak class="mt-4 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-medium text-gray-700">Szczegóły rezerwacji</h4>
                        <ul class="mt-2 space-y-2 text-sm">
                            <li class="flex items-center">
                                <span class="font-medium w-40">ID rezerwacji:</span>
                                <span><?= $booking->id ?></span>
                            </li>
                            <li class="flex items-center">
                                <span class="font-medium w-40">Data rezerwacji:</span>
                                <span><?= (new DateTime($booking->created_at))->format('d.m.Y H:i') ?></span>
                            </li>
                            <li class="flex items-center">
                                <span class="font-medium w-40">Status płatności:</span>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $booking->payment_status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= $booking->payment_status === 'completed' ? 'Opłacona' : 'Oczekująca' ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Informacje o pojeździe</h4>
                        <ul class="mt-2 space-y-2 text-sm">
                            <li class="flex items-center">
                                <span class="font-medium w-40">Model:</span>
                                <span><?= htmlspecialchars($booking->vehicle_name ?? 'Brak danych') ?></span>
                            </li>
                            <li class="flex items-center">
                                <span class="font-medium w-40">Numer rejestracyjny:</span>
                                <span><?= htmlspecialchars($booking->vehicle_registration ?? 'Brak danych') ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <?php if ($booking->status === 'active' || $booking->status === 'pending'): ?>
                <div class="mt-4 pt-3 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">
                            <i class="fas fa-money-bill-wave mr-1"></i> Całkowity koszt:
                            <span class="font-semibold"><?= formatCurrency($booking->total_price ?? 0) ?></span>
                        </span>
                        
                        <?php if ($booking->status === 'pending' && $booking->payment_status !== 'completed'): ?>
                        <a href="/payments/<?= $booking->id ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm">
                            <i class="fas fa-credit-card mr-1"></i> Zapłać teraz
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Booking Timeline -->
                <div class="mt-6">
                    <h4 class="font-medium text-gray-700">Historia rezerwacji</h4>
                    <div class="mt-2 relative">
                        <div class="absolute left-4 top-0 h-full w-0.5 bg-gray-200"></div>
                        
                        <?php if (!empty($booking->history)) : ?>
                            <?php foreach ($booking->history as $historyItem): ?>
                            <div class="relative flex items-start mb-4">
                                <div class="flex items-center justify-center h-8 w-8 rounded-full bg-white border-2 border-blue-500 z-10">
                                    <i class="fas fa-check text-blue-500 text-sm"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($historyItem->title) ?></p>
                                    <p class="text-xs text-gray-500"><?= (new DateTime($historyItem->date))->format('d.m.Y H:i') ?></p>
                                    <?php if (!empty($historyItem->description)): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($historyItem->description) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="ml-10 text-sm text-gray-500">Brak historii dla tej rezerwacji</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
