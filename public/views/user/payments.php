<?php
// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$title = "Płatności";
$userId = $_SESSION['user_id'];

// Helper function for formatting currency
function formatCurrency($amount, $currency = 'PLN') {
    return number_format($amount, 2, ',', ' ') . ' ' . $currency;
}

// Helper function for formatting dates in Polish
function formatPolishDate($date) {
    $timestamp = strtotime($date);
    $months = [
        'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 
        'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'
    ];
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    $hour = date('H:i', $timestamp);
    
    return "$day $month $year, $hour";
}

include BASE_PATH . '/public/views/base.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Płatności</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <!-- Tabs for navigation -->
        <div class="border-b border-gray-200 mb-6" x-data="{ activeTab: 'history' }">
            <ul class="flex flex-wrap -mb-px">
                <li class="mr-2">
                    <a href="#" 
                       @click.prevent="activeTab = 'history'" 
                       :class="{ 'border-blue-500 text-blue-600': activeTab === 'history', 
                                'border-transparent text-gray-500 hover:text-gray-700': activeTab !== 'history' }"
                       class="inline-block p-4 border-b-2 font-medium">
                        Historia transakcji
                    </a>
                </li>
                <li class="mr-2">
                    <a href="#" 
                       @click.prevent="activeTab = 'methods'" 
                       :class="{ 'border-blue-500 text-blue-600': activeTab === 'methods', 
                                'border-transparent text-gray-500 hover:text-gray-700': activeTab !== 'methods' }"
                       class="inline-block p-4 border-b-2 font-medium">
                        Metody płatności
                    </a>
                </li>
            </ul>
        </div>

        <!-- Transaction History Tab -->
        <div x-show="activeTab === 'history'">
            <!-- Search and Filter Section -->
            <div class="flex flex-wrap items-center justify-between mb-6">
                <div class="w-full lg:w-1/2 mb-4 lg:mb-0">
                    <div class="relative">
                        <input type="text" 
                               id="payment-search" 
                               name="q"
                               placeholder="Wyszukaj transakcję..." 
                               class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               hx-trigger="keyup changed delay:500ms"
                               hx-get="/payments/search"
                               hx-target="#payments-table-body"
                               hx-include="[name='payment-filter']">
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex justify-end space-x-4">
                    <select name="payment-filter" 
                            class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            hx-trigger="change"
                            hx-get="/payments/filter"
                            hx-target="#payments-table-body">
                        <option value="all">Wszystkie transakcje</option>
                        <option value="payment">Tylko płatności</option>
                        <option value="refund">Tylko zwroty</option>
                    </select>

                    <select name="payment-sort" 
                            class="border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            hx-trigger="change"
                            hx-get="/payments/sort"
                            hx-target="#payments-table-body">
                        <option value="date_desc">Od najnowszych</option>
                        <option value="date_asc">Od najstarszych</option>
                        <option value="amount_desc">Kwota (malejąco)</option>
                        <option value="amount_asc">Kwota (rosnąco)</option>
                    </select>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Nr transakcji</th>
                            <th class="px-4 py-3">Typ</th>
                            <th class="px-4 py-3">Kwota</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Akcje</th>
                        </tr>
                    </thead>
                    <tbody id="payments-table-body" hx-get="/payments/history" hx-trigger="load">
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="flex justify-center items-center">
                                    <svg class="animate-spin h-6 w-6 text-blue-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Ładowanie transakcji...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="no-transactions" class="hidden text-center py-8 text-gray-500">
                Nie znaleziono żadnych transakcji
            </div>

            <div class="mt-4 flex justify-center">
                <button id="load-more-btn" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                        hx-get="/payments/history?page=2" 
                        hx-target="#payments-table-body" 
                        hx-swap="beforeend"
                        hx-trigger="click">
                    Załaduj więcej
                </button>
            </div>
        </div>
        
        <!-- Payment Methods Tab -->
        <div x-show="activeTab === 'methods'" x-data="paymentMethods()">
            <div class="mb-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-3">Twoje metody płatności</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="payment-methods-container" hx-get="/payments/methods" hx-trigger="load">
                    <!-- Payment methods will be loaded here -->
                    <div class="col-span-full text-center py-4">
                        <div class="flex justify-center items-center">
                            <svg class="animate-spin h-6 w-6 text-blue-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Ładowanie metod płatności...
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button @click="showAddMethodModal = true" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Dodaj nową metodę płatności
                    </button>
                </div>
            </div>
            
            <!-- Modal for adding payment method -->
            <div x-show="showAddMethodModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" @click.outside="showAddMethodModal = false">
                    <h3 class="text-xl font-bold mb-4">Dodaj nową metodę płatności</h3>
                    
                    <form id="add-payment-method-form" @submit.prevent="submitPaymentMethod">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Typ płatności</label>
                            <select x-model="newMethod.type" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="credit_card">Karta kredytowa</option>
                                <option value="bank_transfer">Przelew bankowy</option>
                                <option value="blik">BLIK</option>
                            </select>
                        </div>
                        
                        <div x-show="newMethod.type === 'credit_card'">
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Numer karty</label>
                                <input type="text" x-model="newMethod.card_number" @input="formatCardNumber" placeholder="1234 5678 9012 3456" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" maxlength="19">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Data ważności</label>
                                    <input type="text" x-model="newMethod.expiry_date" placeholder="MM/RR" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" maxlength="5">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">CVV</label>
                                    <input type="text" x-model="newMethod.cvv" placeholder="123" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" maxlength="3">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Nazwa na karcie</label>
                                <input type="text" x-model="newMethod.cardholder_name" placeholder="Jan Kowalski" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div x-show="newMethod.type === 'bank_transfer'">
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Nazwa banku</label>
                                <input type="text" x-model="newMethod.bank_name" placeholder="Nazwa banku" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Numer konta</label>
                                <input type="text" x-model="newMethod.account_number" placeholder="PL61 1090 1014 0000 0712 1981 2874" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div x-show="newMethod.type === 'blik'">
                            <div class="mb-4">
                                <p class="text-gray-600 mb-2">
                                    Metoda BLIK zostanie dodana do Twojego konta. Kod BLIK będzie generowany przy każdej płatności.
                                </p>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="newMethod.is_default" class="rounded text-blue-500 mr-2">
                                <span class="text-gray-700">Ustaw jako domyślną metodę płatności</span>
                            </label>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="showAddMethodModal = false" class="px-4 py-2 border rounded-lg">Anuluj</button>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                Zapisz metodę płatności
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal for payment method details -->
            <div x-show="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-cloak>
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" @click.outside="showDetailModal = false">
                    <h3 class="text-xl font-bold mb-4">Szczegóły metody płatności</h3>
                    
                    <div id="payment-method-details">
                        <!-- Details will be loaded here via HTMX -->
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="showDetailModal = false" class="px-4 py-2 border rounded-lg">Zamknij</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Detail Modal -->
<div id="payment-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Szczegóły transakcji</h3>
            <button onclick="document.getElementById('payment-detail-modal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div id="payment-detail-content">
            <!-- Payment details will be loaded here -->
        </div>
    </div>
</div>

<script>
    // Alpine.js component for payment methods
    function paymentMethods() {
        return {
            showAddMethodModal: false,
            showDetailModal: false,
            newMethod: {
                type: 'credit_card',
                card_number: '',
                expiry_date: '',
                cvv: '',
                cardholder_name: '',
                bank_name: '',
                account_number: '',
                is_default: false
            },
            
            formatCardNumber(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                this.newMethod.card_number = formattedValue;
            },
            
            submitPaymentMethod() {
                // Get card last 4 digits if credit card
                if (this.newMethod.type === 'credit_card') {
                    const cardNumber = this.newMethod.card_number.replace(/\s+/g, '');
                    this.newMethod.card_last4 = cardNumber.slice(-4);
                    this.newMethod.card_brand = this.detectCardBrand(cardNumber);
                }
                
                // Submit via AJAX to avoid page reload
                fetch('/payments/methods/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(this.newMethod),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Refresh payment methods list
                        htmx.trigger('#payment-methods-container', 'load');
                        this.showAddMethodModal = false;
                        
                        // Reset form
                        this.newMethod = {
                            type: 'credit_card',
                            card_number: '',
                            expiry_date: '',
                            cvv: '',
                            cardholder_name: '',
                            bank_name: '',
                            account_number: '',
                            is_default: false
                        };
                        
                        // Show success message
                        CarFuse.showToast('Sukces', 'Metoda płatności została dodana pomyślnie.', 'success');
                    } else {
                        CarFuse.showToast('Błąd', data.message || 'Nie udało się dodać metody płatności.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    CarFuse.showToast('Błąd', 'Wystąpił błąd podczas dodawania metody płatności.', 'error');
                });
            },
            
            detectCardBrand(cardNumber) {
                // Simple card brand detection based on first digits
                if (/^4/.test(cardNumber)) return 'Visa';
                if (/^5[1-5]/.test(cardNumber)) return 'Mastercard';
                if (/^3[47]/.test(cardNumber)) return 'American Express';
                return 'Nieznana karta';
            },
            
            viewPaymentMethodDetails(id) {
                this.showDetailModal = true;
                
                // Load payment method details using HTMX
                htmx.ajax('GET', `/payments/methods/${id}`, {
                    target: '#payment-method-details',
                    swap: 'innerHTML'
                });
            },
            
            deletePaymentMethod(id) {
                if (confirm('Czy na pewno chcesz usunąć tę metodę płatności?')) {
                    fetch(`/payments/methods/${id}/delete`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Refresh payment methods list
                            htmx.trigger('#payment-methods-container', 'load');
                            CarFuse.showToast('Sukces', 'Metoda płatności została usunięta.', 'success');
                        } else {
                            CarFuse.showToast('Błąd', data.message || 'Nie udało się usunąć metody płatności.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        CarFuse.showToast('Błąd', 'Wystąpił błąd podczas usuwania metody płatności.', 'error');
                    });
                }
            }
        }
    }
    
    // Function to show payment details
    function showPaymentDetails(id) {
        const modal = document.getElementById('payment-detail-modal');
        modal.classList.remove('hidden');
        
        // Load payment details using HTMX
        htmx.ajax('GET', `/payments/${id}/details`, {
            target: '#payment-detail-content',
            swap: 'innerHTML'
        });
    }
    
    // Function to download invoice
    function downloadInvoice(id) {
        window.location.href = `/payments/${id}/invoice`;
    }

    // Register with HTMX events for better UX
    document.addEventListener('htmx:afterSwap', function(evt) {
        if (evt.detail.target.id === 'payments-table-body') {
            // Check if there are any results
            const hasResults = evt.detail.target.querySelector('tr') !== null;
            document.getElementById('no-transactions').classList.toggle('hidden', hasResults);
        }
    });
</script>
