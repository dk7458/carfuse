<div class="space-y-6">
    <!-- Booking Options -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Opcje rezerwacji</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="min_rental_period" class="block text-sm font-medium text-gray-700 mb-1">Minimalny okres wynajmu (dni)</label>
                    <input type="number" id="min_rental_period" name="min_rental_period" min="1"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.min_rental_period"
                           @change="markAsChanged($event)">
                    <div x-show="validationErrors.min_rental_period" class="text-red-500 text-xs mt-1" x-text="validationErrors.min_rental_period"></div>
                </div>
                <div>
                    <label for="max_rental_period" class="block text-sm font-medium text-gray-700 mb-1">Maksymalny okres wynajmu (dni)</label>
                    <input type="number" id="max_rental_period" name="max_rental_period" min="1"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.max_rental_period"
                           @change="markAsChanged($event)">
                    <div x-show="validationErrors.max_rental_period" class="text-red-500 text-xs mt-1" x-text="validationErrors.max_rental_period"></div>
                </div>
            </div>
            
            <div>
                <label for="advance_booking_limit" class="block text-sm font-medium text-gray-700 mb-1">Limit rezerwacji z wyprzedzeniem (dni)</label>
                <input type="number" id="advance_booking_limit" name="advance_booking_limit" min="1"
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                       x-model="settings.advance_booking_limit"
                       @change="markAsChanged($event)">
            </div>
            
            <div>
                <label for="buffer_time" class="block text-sm font-medium text-gray-700 mb-1">Czas buforowy między rezerwacjami (godziny)</label>
                <input type="number" id="buffer_time" name="buffer_time" min="0" step="0.5"
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                       x-model="settings.buffer_time"
                       @change="markAsChanged($event)">
                <div x-show="validationErrors.buffer_time" class="text-red-500 text-xs mt-1" x-text="validationErrors.buffer_time"></div>
            </div>
            
            <div>
                <label for="default_pickup_location" class="block text-sm font-medium text-gray-700 mb-1">Domyślna lokalizacja odbioru</label>
                <input type="text" id="default_pickup_location" name="default_pickup_location"
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                       x-model="settings.default_pickup_location"
                       @change="markAsChanged($event)">
            </div>
        </div>
    </div>
    
    <!-- Pricing Options -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Opcje cenowe</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="default_deposit" class="block text-sm font-medium text-gray-700 mb-1">Domyślna kaucja</label>
                    <input type="number" id="default_deposit" name="default_deposit" min="0"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.default_deposit"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="late_return_fee" class="block text-sm font-medium text-gray-700 mb-1">Opłata za późny zwrot (za godzinę)</label>
                    <input type="number" id="late_return_fee" name="late_return_fee" min="0"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.late_return_fee"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_dynamic_pricing"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Włącz dynamiczne ceny</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">Automatycznie dostosowuje ceny w zależności od popytu i sezonu.</p>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.require_advance_payment"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Wymagaj płatności zaliczki</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">Klienci muszą zapłacić część ceny z góry, aby potwierdzić rezerwację.</p>
            </div>
            
            <div>
                <label for="cancellation_policy" class="block text-sm font-medium text-gray-700 mb-1">Polityka anulowania</label>
                <select id="cancellation_policy" name="cancellation_policy"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        x-model="settings.cancellation_policy"
                        @change="markAsChanged($event)">
                    <option value="flexible">Elastyczna (pełny zwrot 24h przed odbiorem)</option>
                    <option value="moderate">Umiarkowana (pełny zwrot 72h przed odbiorem)</option>
                    <option value="strict">Rygorystyczna (zwrot 50% 7 dni przed odbiorem)</option>
                    <option value="custom">Niestandardowa</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Custom cancellation policy configuration, shown conditionally -->
    <div class="bg-gray-50 p-4 rounded-md" x-show="settings.cancellation_policy === 'custom'" x-transition>
        <h3 class="font-medium text-gray-700 mb-3">Niestandardowa polityka anulowania</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="full_refund_hours" class="block text-sm font-medium text-gray-700 mb-1">Godziny przed odbiorem (pełny zwrot)</label>
                    <input type="number" id="full_refund_hours" name="full_refund_hours" min="0"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.full_refund_hours"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="partial_refund_percent" class="block text-sm font-medium text-gray-700 mb-1">Procent zwrotu po tym terminie</label>
                    <input type="number" id="partial_refund_percent" name="partial_refund_percent" min="0" max="100"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.partial_refund_percent"
                           @change="markAsChanged($event)">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="button" 
                @click="saveTabSettings('booking')"
                class="settings-save hidden items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="isSaving || hasValidationErrors()"
                :class="{'opacity-75 cursor-wait': isSaving, 'opacity-50 cursor-not-allowed': hasValidationErrors()}">
            <span x-show="!isSaving">Zapisz ustawienia rezerwacji</span>
            <span x-show="isSaving" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Zapisywanie...
            </span>
        </button>
    </div>
</div>
