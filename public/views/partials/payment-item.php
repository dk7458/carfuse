<?php
// Required variables: $transaction with payment data
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

$statusClass = $statusClasses[$transaction['status']] ?? 'bg-gray-100 text-gray-800';
?>

<tr class="border-b hover:bg-gray-50">
    <td class="px-4 py-3">
        <?= formatPolishDate($transaction['created_at']) ?>
    </td>
    <td class="px-4 py-3 font-medium">
        <?= htmlspecialchars($transaction['transaction_id']) ?>
    </td>
    <td class="px-4 py-3">
        <?= $typeLabels[$transaction['type']] ?? htmlspecialchars($transaction['type']) ?>
    </td>
    <td class="px-4 py-3 font-medium">
        <?= formatCurrency($transaction['amount'], $transaction['currency'] ?? 'PLN') ?>
    </td>
    <td class="px-4 py-3">
        <span class="px-2 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
            <?= $statusLabels[$transaction['status']] ?? htmlspecialchars($transaction['status']) ?>
        </span>
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center space-x-2">
            <button onclick="showPaymentDetails('<?= $transaction['id'] ?>')" 
                    class="text-blue-500 hover:text-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
            
            <?php if ($transaction['status'] === 'completed'): ?>
            <button onclick="downloadInvoice('<?= $transaction['id'] ?>')" 
                    class="text-green-500 hover:text-green-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
            </button>
            <?php endif; ?>
        </div>
    </td>
</tr>
