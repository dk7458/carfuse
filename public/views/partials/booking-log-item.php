<div class="booking-log-item py-2 border-b border-gray-200 last:border-0">
    <div class="flex items-start">
        <!-- Status Icon -->
        <div class="mr-3">
            <?php if ($log['action'] === 'booking_created'): ?>
                <span class="flex items-center justify-center h-8 w-8 rounded-full bg-green-100">
                    <svg class="h-5 w-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </span>
            <?php elseif ($log['action'] === 'booking_canceled'): ?>
                <span class="flex items-center justify-center h-8 w-8 rounded-full bg-red-100">
                    <svg class="h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </span>
            <?php elseif ($log['action'] === 'booking_rescheduled'): ?>
                <span class="flex items-center justify-center h-8 w-8 rounded-full bg-yellow-100">
                    <svg class="h-5 w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            <?php elseif ($log['action'] === 'booking_confirmed'): ?>
                <span class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100">
                    <svg class="h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            <?php else: ?>
                <span class="flex items-center justify-center h-8 w-8 rounded-full bg-gray-100">
                    <svg class="h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Log Content -->
        <div class="flex-1">
            <div class="flex justify-between items-start">
                <p class="text-sm font-medium text-gray-900">
                    <?php 
                    $actionText = match($log['action']) {
                        'booking_created' => 'Rezerwacja utworzona',
                        'booking_canceled' => 'Rezerwacja anulowana',
                        'booking_rescheduled' => 'Zmiana terminu rezerwacji',
                        'booking_confirmed' => 'Rezerwacja potwierdzona',
                        'booking_viewed' => 'Podgląd rezerwacji',
                        'payment_processed' => 'Płatność zrealizowana',
                        'refund_processed' => 'Zwrot środków',
                        default => ucfirst(str_replace('_', ' ', $log['action']))
                    };
                    echo htmlspecialchars($actionText);
                    ?>
                </p>
                <span class="text-xs text-gray-500">
                    <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                <?= htmlspecialchars($log['description']) ?>
            </p>
            
            <?php if (!empty($log['metadata'])): ?>
                <?php if (is_string($log['metadata'])) {
                    $metadata = json_decode($log['metadata'], true) ?? [];
                } else {
                    $metadata = $log['metadata'];
                } ?>
                
                <?php if (!empty($metadata) && $log['action'] === 'booking_rescheduled'): ?>
                    <div class="mt-2 text-xs text-gray-500">
                        <div class="flex">
                            <span class="font-medium w-24">Nowa data odbioru:</span>
                            <span><?= date('d.m.Y H:i', strtotime($metadata['new_pickup'] ?? '')) ?></span>
                        </div>
                        <div class="flex mt-1">
                            <span class="font-medium w-24">Nowa data zwrotu:</span>
                            <span><?= date('d.m.Y H:i', strtotime($metadata['new_dropoff'] ?? '')) ?></span>
                        </div>
                    </div>
                <?php elseif (!empty($metadata) && $log['action'] === 'refund_processed'): ?>
                    <div class="mt-2 text-xs text-gray-500">
                        <div class="flex">
                            <span class="font-medium w-24">Kwota zwrotu:</span>
                            <span><?= number_format($metadata['refund_amount'] ?? 0, 2, ',', ' ') ?> zł</span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
