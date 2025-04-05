<div class="space-y-6">
    <!-- Password Security -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Bezpieczeństwo haseł</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password_min_length" class="block text-sm font-medium text-gray-700 mb-1">Minimalna długość hasła</label>
                    <input type="number" id="password_min_length" name="password_min_length" min="6" max="30"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.password_min_length"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="password_expiry_days" class="block text-sm font-medium text-gray-700 mb-1">Wygaśnięcie hasła (dni, 0=nigdy)</label>
                    <input type="number" id="password_expiry_days" name="password_expiry_days" min="0"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.password_expiry_days"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.password_require_uppercase"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Wymagaj wielkich liter</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.password_require_numbers"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Wymagaj cyfr</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.password_require_special"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Wymagaj znaków specjalnych</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Login Security -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Bezpieczeństwo logowania</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="login_max_attempts" class="block text-sm font-medium text-gray-700 mb-1">Maksymalna liczba prób logowania</label>
                    <input type="number" id="login_max_attempts" name="login_max_attempts" min="1" max="20"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.login_max_attempts"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="login_lockout_minutes" class="block text-sm font-medium text-gray-700 mb-1">Czas blokady (minuty)</label>
                    <input type="number" id="login_lockout_minutes" name="login_lockout_minutes" min="1"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.login_lockout_minutes"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.login_require_captcha"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Wymagaj CAPTCHA po nieudanych próbach logowania</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.login_2fa_enabled"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Włącz uwierzytelnianie dwuskładnikowe (2FA)</span>
                </label>
            </div>
            
            <div x-show="settings.login_2fa_enabled" x-transition>
                <label for="login_2fa_method" class="block text-sm font-medium text-gray-700 mb-1">Metoda 2FA</label>
                <select id="login_2fa_method" name="login_2fa_method"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        x-model="settings.login_2fa_method"
                        @change="markAsChanged($event)">
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                    <option value="app">Aplikacja autentykacyjna</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Session Security -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Bezpieczeństwo sesji</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="session_lifetime_minutes" class="block text-sm font-medium text-gray-700 mb-1">Czas życia sesji (minuty)</label>
                    <input type="number" id="session_lifetime_minutes" name="session_lifetime_minutes" min="5"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.session_lifetime_minutes"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="session_idle_timeout" class="block text-sm font-medium text-gray-700 mb-1">Limit bezczynności (minuty)</label>
                    <input type="number" id="session_idle_timeout" name="session_idle_timeout" min="0"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.session_idle_timeout"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.session_regenerate_id"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Regeneruj ID sesji po zalogowaniu</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.session_use_fingerprint"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Używaj odcisku przeglądarki</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">Pomaga wykrywać kradzież sesji poprzez weryfikację urządzenia.</p>
            </div>
        </div>
    </div>
    
    <!-- HTTPS & SSL -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">SSL i HTTPS</h3>
        <div class="space-y-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.force_https"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Wymuś HTTPS</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_hsts"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Włącz HSTS (HTTP Strict Transport Security)</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_csp"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Włącz CSP (Content Security Policy)</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="button" 
                @click="saveTabSettings('security')"
                class="settings-save hidden items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="isSaving || hasValidationErrors()"
                :class="{'opacity-75 cursor-wait': isSaving, 'opacity-50 cursor-not-allowed': hasValidationErrors()}">
            <span x-show="!isSaving">Zapisz ustawienia bezpieczeństwa</span>
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
