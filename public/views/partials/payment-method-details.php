<?php
// Required variables: $method with payment method details
$isDefault = isset($method['is_default']) && $method['is_default'];
$createdDate = isset($method['created_at']) ? formatPolishDate($method['created_at']) : 'Nieznana data';
?>

<div class="space-y-4">
    <?php if ($method['type'] === 'credit_card'): ?>
        <div>
            <p class="text-sm text-gray-500">Rodzaj karty</p>
            <p class="font-medium"><?= htmlspecialchars($method['card_brand'] ?? 'Karta kredytowa') ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Numer karty</p>
            <p class="font-medium">•••• •••• •••• <?= htmlspecialchars($method['card_last4'] ?? '????') ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Data ważności</p>
            <p class="font-medium"><?= htmlspecialchars($method['expiry_date'] ?? 'MM/RR') ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Posiadacz karty</p>
            <p class="font-medium"><?= htmlspecialchars($method['cardholder_name'] ?? 'Nie podano') ?></p>
        </div>
    <?php elseif ($method['type'] === 'bank_transfer'): ?>
        <div>
            <p class="text-sm text-gray-500">Nazwa banku</p>
            <p class="font-medium"><?= htmlspecialchars($method['bank_name'] ?? 'Nie podano') ?></p>
        </div>
        
        <div>
            <p class="text-sm text-gray-500">Numer konta</p>
            <p class="font-medium"><?= htmlspecialchars($method['account_number'] ?? 'Nie podano') ?></p>
        </div>
    <?php elseif ($method['type'] === 'blik'): ?>
        <div>
            <p class="text-sm text-gray-500">Metoda płatności</p>
            <p class="font-medium">BLIK</p>
        </div>
        
        <div>
            <p class="text-gray-600">
                Ta metoda umożliwia płatności jednorazowymi kodami BLIK generowanymi przy każdej transakcji.
            </p>
        </div>
    <?php endif; ?>
    
    <div>
        <p class="text-sm text-gray-500">Dodana</p>
        <p class="font-medium"><?= $createdDate ?></p>
    </div>
    
    <?php if ($isDefault): ?>
        <div class="bg-green-50 p-3 rounded-lg text-green-700">
            <p class="font-medium">Domyślna metoda płatności</p>
            <p class="text-sm">Ta metoda jest używana automatycznie podczas płatności</p>
        </div>
    <?php else: ?>
        <button 
            onclick="setDefaultPaymentMethod('<?= $method['id'] ?>')" 
            class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
            Ustaw jako domyślną
        </button>
    <?php endif; ?>
    
    <div class="border-t pt-4 flex justify-end">
        <button 
            onclick="deletePaymentMethod('<?= $method['id'] ?>')" 
            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Usuń metodę płatności
        </button>
    </div>
</div>
