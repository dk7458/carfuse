<div class="space-y-6">
    <!-- Email Configuration -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Konfiguracja E-mail</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email_sender" class="block text-sm font-medium text-gray-700 mb-1">Adres nadawcy</label>
                    <input type="email" id="email_sender" name="email_sender"
                           class="bg-white border border-gray-300 text-sm rounded-lg p-2.5 w-full focus:ring-blue-500 focus:border-blue-500"
                           x-model="settings.email_sender"
                           @change="markAsChanged($event)">
                    <div x-show="validationErrors.email_sender" class="text-red-500 text-xs mt-1" x-text="validationErrors.email_sender"></div>
                </div>
                
                <div>
                    <label for="email_sender_name" class="block text-sm font-medium text-gray-700 mb-1">Nazwa nadawcy</label>
                    <input type="text" id="email_sender_name" name="email_sender_name"
                           class="bg-white border border-gray-300 text-sm rounded-lg p-2.5 w-full focus:ring-blue-500 focus:border-blue-500"
                           x-model="settings.email_sender_name"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                    <input type="text" id="smtp_host" name="smtp_host"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.smtp_host"
                           @change="markAsChanged($event)">
                    <div x-show="validationErrors.smtp_host" class="text-red-500 text-xs mt-1" x-text="validationErrors.smtp_host"></div>
                </div>
                <div>
                    <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-1">Port SMTP</label>
                    <input type="number" id="smtp_port" name="smtp_port" min="1" max="65535"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.smtp_port"
                           @change="markAsChanged($event)">
                    <div x-show="validationErrors.smtp_port" class="text-red-500 text-xs mt-1" x-text="validationErrors.smtp_port"></div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-1">Nazwa użytkownika SMTP</label>
                    <input type="text" id="smtp_username" name="smtp_username"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.smtp_username"
                           @change="markAsChanged($event)">
                </div>
                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-1">Hasło SMTP</label>
                    <input type="password" id="smtp_password" name="smtp_password"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.smtp_password"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <div>
                <label for="email_encryption" class="block text-sm font-medium text-gray-700 mb-1">Szyfrowanie</label>
                <select id="email_encryption" name="email_encryption"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        x-model="settings.email_encryption"
                        @change="markAsChanged($event)">
                    <option value="none">Brak</option>
                    <option value="ssl">SSL</option>
                    <option value="tls">TLS</option>
                </select>
            </div>
            
            <div>
                <button type="button" 
                        @click="testEmailConnection()"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        :disabled="testingConnection"
                        :class="{'opacity-75 cursor-wait': testingConnection}">
                    <span x-show="!testingConnection"><i class="fas fa-paper-plane mr-2"></i> Testuj połączenie e-mail</span>
                    <span x-show="testingConnection" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Testowanie...
                    </span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Notification Types -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Typy powiadomień</h3>
        <div class="space-y-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.notify_booking_confirmation"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Potwierdzenie rezerwacji</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.notify_booking_reminder"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Przypomnienie o rezerwacji</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.notify_return_reminder"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Przypomnienie o zwrocie</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.notify_feedback_request"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Prośba o opinię</span>
                </label>
            </div>
            
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.notify_special_offers"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Specjalne oferty i promocje</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- SMS Notifications -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Powiadomienia SMS</h3>
        <div class="space-y-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_sms_notifications"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Włącz powiadomienia SMS</span>
                </label>
            </div>
            
            <div x-show="settings.enable_sms_notifications" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sms_api_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz API SMS</label>
                        <input type="password" id="sms_api_key" name="sms_api_key"
                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                               x-model="settings.sms_api_key"
                               @change="markAsChanged($event)">
                    </div>
                    
                    <div>
                        <label for="sms_sender_id" class="block text-sm font-medium text-gray-700 mb-1">ID nadawcy SMS</label>
                        <input type="text" id="sms_sender_id" name="sms_sender_id"
                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                               x-model="settings.sms_sender_id"
                               @change="markAsChanged($event)">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="sms_provider" class="block text-sm font-medium text-gray-700 mb-1">Dostawca SMS</label>
                    <select id="sms_provider" name="sms_provider"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.sms_provider"
                           @change="markAsChanged($event)">
                        <option value="twilio">Twilio</option>
                        <option value="smsapi">SMSAPI</option>
                        <option value="messagebird">MessageBird</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Push Notifications -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Powiadomienia Push</h3>
        <div class="space-y-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_push_notifications"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Włącz powiadomienia Push</span>
                </label>
            </div>
            
            <div x-show="settings.enable_push_notifications" x-transition>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="firebase_api_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz API Firebase</label>
                        <input type="password" id="firebase_api_key" name="firebase_api_key"
                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                               x-model="settings.firebase_api_key"
                               @change="markAsChanged($event)">
                    </div>
                    
                    <div>
                        <label for="firebase_project_id" class="block text-sm font-medium text-gray-700 mb-1">ID projektu Firebase</label>
                        <input type="text" id="firebase_project_id" name="firebase_project_id"
                               class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                               x-model="settings.firebase_project_id"
                               @change="markAsChanged($event)">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="button" 
                @click="saveTabSettings('notifications')"
                class="settings-save hidden items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="isSaving || hasValidationErrors()"
                :class="{'opacity-75 cursor-wait': isSaving, 'opacity-50 cursor-not-allowed': hasValidationErrors()}">
            <span x-show="!isSaving">Zapisz ustawienia powiadomień</span>
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
