<?php
// Required variables: $details with payment details
$statusClasses = [
    'completed' => 'bg-green-100 text-green-800',
    'pending' => 'bg-yellow-100 text-yellow-800',
    'failed' => 'bg-red-100 text-red-800',
    'refunded' => 'bg-purple-100 text-purple-800'
];

$typeLabels = [
    'payment' => 'Płatność',
    'refund' => 'Zwrot'
];

$statusLabels = [
    'completed' => 'Zakończona',
    'pending' => 'W trakcie',
    'failed' => 'Nieudana',
    'refunded' => 'Zwrócona'
];

$statusClass = $statusClasses[$details['status']] ?? 'bg-gray-100 text-gray-800';
$statusLabel = $statusLabels[$details['status']] ?? $details['status'];
$typeLabel = $typeLabels[$details['type']] ?? $details['type'];
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <h4 class="text-lg font-semibold mb-3">Informacje o płatności</h4>
        
        <div class="space-y-3">
            <div>
                <p class="text-sm text-gray-500">ID Transakcji</p>
                <p class="font-medium"><?= htmlspecialchars($details['transaction_id']) ?></p>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Data transakcji</p>
                <p class="font-medium"><?= formatPolishDate($details['created_at']) ?></p>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Typ</p>
                <p class="font-medium"><?= $typeLabel ?></p>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Kwota</p>
                <p class="font-medium"><?= formatCurrency($details['amount'], $details['currency'] ?? 'PLN') ?></p>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                    <?= $statusLabel ?>
                </p>
            </div>
        </div>
    </div>
    
    <div>
        <h4 class="text-lg font-semibold mb-3">Szczegóły zamówienia</h4>
        
        <div class="space-y-3">
            <?php if (isset($details['booking_id'])): ?>
            <div>
                <p class="text-sm text-gray-500">ID Rezerwacji</p>
                <p class="font-medium"><?= htmlspecialchars($details['booking_id']) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($details['booking_details'])): ?>
            <div>
                <p class="text-sm text-gray-500">Szczegóły rezerwacji</p>
                <p class="font-medium"><?= htmlspecialchars($details['booking_details']) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($details['payment_method'])): ?>
            <div>
                <p class="text-sm text-gray-500">Metoda płatności</p>
                <p class="font-medium">
                    <?php 
                    $method = $details['payment_method'];
                    if ($method['type'] === 'credit_card') {
                        echo htmlspecialchars($method['card_brand'] ?? 'Karta') . ' •••• ' . htmlspecialchars($method['card_last4'] ?? '????');
                    } elseif ($method['type'] === 'bank_transfer') {
                        echo 'Przelew bankowy';
                    } elseif ($method['type'] === 'blik') {
                        echo 'BLIK';
                    } else {
                        echo htmlspecialchars($method['type']);
                    }
                    ?>
                </p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($details['gateway'])): ?>
            <div>
                <p class="text-sm text-gray-500">Bramka płatności</p>
                <p class="font-medium"><?= ucfirst(htmlspecialchars($details['gateway'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($details['status'] === 'completed' && $details['type'] === 'payment'): ?>
        <div class="mt-6">
            <button 
                onclick="downloadInvoice('<?= $details['id'] ?>')" 
                class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                Pobierz fakturę
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($details['refund_details']) && !empty($details['refund_details'])): ?>
    <div class="col-span-1 md:col-span-2">
        <h4 class="text-lg font-semibold mb-3">Informacje o zwrocie</h4>
        
        <div class="space-y-3 bg-gray-50 p-4 rounded-lg">
            <div>
                <p class="text-sm text-gray-500">Data zwrotu</p>
                <p class="font-medium"><?= formatPolishDate($details['refund_details']['created_at']) ?></p>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Kwota zwrotu</p>
                <p class="font-medium"><?= formatCurrency($details['refund_details']['amount'], $details['currency'] ?? 'PLN') ?></p>
            </div>
            
            <div>
                <p class="text-sm text-gray-500">Powód zwrotu</p>
                <p class="font-medium"><?= htmlspecialchars($details['refund_details']['reason'] ?? 'Nie podano') ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($details['additional_info']) && !empty($details['additional_info'])): ?>
<div class="mt-6 pt-6 border-t">
    <h4 class="text-lg font-semibold mb-3">Dodatkowe informacje</h4>
    <div class="text-gray-700">
        <?= nl2br(htmlspecialchars($details['additional_info'])) ?>
    </div>
</div>
<?php endif; ?>
