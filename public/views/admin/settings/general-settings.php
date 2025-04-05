<div class="space-y-6">
    <!-- Company Info -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Informacje o firmie</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Nazwa firmy</label>
                    <input type="text" id="company_name" name="company_name" 
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.company_name"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="company_address" class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
                    <input type="text" id="company_address" name="company_address" 
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                           x-model="settings.company_address"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="vat_id" class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                    <input type="text" id="vat_id" name="vat_id" 
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.vat_id"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Telefon kontaktowy</label>
                    <input type="tel" id="phone_number" name="phone_number" 
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" 
                           x-model="settings.phone_number"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div>
                <label for="email_contact" class="block text-sm font-medium text-gray-700 mb-1">Email kontaktowy</label>
                <input type="email" id="email_contact" name="email_contact" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                       x-model="settings.email_contact"
                       @change="markAsChanged($event)">
                <div x-show="validationErrors.email_contact" class="text-red-500 text-xs mt-1" x-text="validationErrors.email_contact"></div>
            </div>
        </div>
    </div>
    
    <!-- System Settings -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Ustawienia systemowe</h3>
        <div class="space-y-4">
            <div>
                <label for="site_title" class="block text-sm font-medium text-gray-700 mb-1">Tytuł strony</label>
                <input type="text" id="site_title" name="site_title" 
                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                       x-model="settings.site_title"
                       @change="markAsChanged($event)">
            </div>
            
            <div>
                <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Opis strony (meta)</label>
                <textarea id="meta_description" name="meta_description" rows="2"
                          class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                          x-model="settings.meta_description"
                          @change="markAsChanged($event)"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="default_language" class="block text-sm font-medium text-gray-700 mb-1">Domyślny język</label>
                    <select id="default_language" name="default_language" 
                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                            x-model="settings.default_language"
                            @change="markAsChanged($event)">
                        <option value="pl">Polski</option>
                        <option value="en">Angielski</option>
                        <option value="de">Niemiecki</option>
                        <option value="fr">Francuski</option>
                    </select>
                </div>
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Strefa czasowa</label>
                    <select id="timezone" name="timezone" 
                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                            x-model="settings.timezone"
                            @change="markAsChanged($event)">
                        <option value="Europe/Warsaw">Europa/Warszawa</option>
                        <option value="Europe/London">Europa/Londyn</option>
                        <option value="Europe/Berlin">Europa/Berlin</option>
                        <option value="America/New_York">Ameryka/Nowy Jork</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.maintenance_mode"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Tryb konserwacji</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">Gdy włączony, strona będzie niedostępna dla zwykłych użytkowników.</p>
            </div>
        </div>
    </div>

    <!-- Currency Settings -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Waluta i płatności</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Waluta</label>
                    <select id="currency" name="currency" 
                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                            x-model="settings.currency"
                            @change="markAsChanged($event)">
                        <option value="PLN">Złoty (PLN)</option>
                        <option value="EUR">Euro (EUR)</option>
                        <option value="USD">Dolar (USD)</option>
                        <option value="GBP">Funt (GBP)</option>
                    </select>
                </div>
                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Stawka VAT (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="1"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.tax_rate"
                           @change="markAsChanged($event)">
                    <div x-show="validationErrors.tax_rate" class="text-red-500 text-xs mt-1" x-text="validationErrors.tax_rate"></div>
                </div>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_online_payments"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Włącz płatności online</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="button" 
                @click="saveTabSettings('general')"
                class="settings-save hidden items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="isSaving || hasValidationErrors()"
                :class="{'opacity-75 cursor-wait': isSaving, 'opacity-50 cursor-not-allowed': hasValidationErrors()}">
            <span x-show="!isSaving">Zapisz ustawienia ogólne</span>
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
