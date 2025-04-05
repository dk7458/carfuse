<?php
// Required variables: $method with payment method data
$isDefault = isset($method['is_default']) && $method['is_default'];

// Determine card icon based on type
$cardIcon = 'credit-card';
$bgColor = 'bg-white';
$brandName = '';

if ($method['type'] === 'credit_card') {
    // Determine card brand
    $brand = strtolower($method['card_brand'] ?? '');
    if (strpos($brand, 'visa') !== false) {
        $cardIcon = 'cc-visa';
        $bgColor = 'bg-blue-50';
        $brandName = 'Visa';
    } elseif (strpos($brand, 'mastercard') !== false) {
        $cardIcon = 'cc-mastercard';
        $bgColor = 'bg-yellow-50';
        $brandName = 'Mastercard';
    } else {
        $brandName = ucfirst($brand);
    }
} elseif ($method['type'] === 'bank_transfer') {
    $cardIcon = 'university';
    $bgColor = 'bg-green-50';
} elseif ($method['type'] === 'blik') {
    $cardIcon = 'mobile-alt';
    $bgColor = 'bg-purple-50';
}
?>

<div class="border rounded-lg shadow <?= $bgColor ?> p-4 relative">
    <?php if ($isDefault): ?>
    <div class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-bl-lg">
        Domyślna
    </div>
    <?php endif; ?>
    
    <div class="flex items-center mb-3">
        <div class="text-2xl text-gray-700 mr-3">
            <i class="fas fa-<?= $cardIcon ?>"></i>
        </div>
        <div>
            <?php if ($method['type'] === 'credit_card'): ?>
                <h4 class="font-semibold"><?= $brandName ?> •••• <?= htmlspecialchars($method['card_last4'] ?? '????') ?></h4>
                <p class="text-sm text-gray-500">Wygasa: <?= htmlspecialchars($method['expiry_date'] ?? 'MM/RR') ?></p>
            <?php elseif ($method['type'] === 'bank_transfer'): ?>
                <h4 class="font-semibold"><?= htmlspecialchars($method['bank_name'] ?? 'Przelew bankowy') ?></h4>
                <p class="text-sm text-gray-500">Konto: •••• <?= substr($method['account_number'] ?? '', -4) ?></p>
            <?php elseif ($method['type'] === 'blik'): ?>
                <h4 class="font-semibold">BLIK</h4>
                <p class="text-sm text-gray-500">Płatność kodem jednorazowym</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex justify-end space-x-2">
        <button 
            onclick="paymentMethods().viewPaymentMethodDetails('<?= $method['id'] ?>')"
            class="text-blue-500 hover:text-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </button>
        
        <?php if (!$isDefault): ?>
        <button 
            onclick="paymentMethods().setDefaultPaymentMethod('<?= $method['id'] ?>')"
            class="text-green-500 hover:text-green-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </button>
        <?php endif; ?>
        
        <button 
            onclick="paymentMethods().deletePaymentMethod('<?= $method['id'] ?>')"
            class="text-red-500 hover:text-red-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </div>
</div>
