<div class="space-y-6">
    <!-- Payment Gateways -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Bramy płatności</h3>
        <div class="space-y-4">
            <div>
                <label for="payment_gateway_primary" class="block text-sm font-medium text-gray-700 mb-1">Podstawowa bramka płatności</label>
                <select id="payment_gateway_primary" name="payment_gateway_primary"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        x-model="settings.payment_gateway_primary"
                        @change="markAsChanged($event)">
                    <option value="stripe">Stripe</option>
                    <option value="paypal">PayPal</option>
                    <option value="przelewy24">Przelewy24</option>
                    <option value="payu">PayU</option>
                    <option value="dotpay">Dotpay</option>
                </select>
            </div>
            
            <!-- Stripe Settings -->
            <div x-show="settings.payment_gateway_primary === 'stripe'" x-transition>
                <div class="mt-4 p-3 bg-gray-100 rounded-md border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Ustawienia Stripe</h4>
                    <div class="space-y-3">
                        <div>
                            <label for="stripe_public_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz publiczny</label>
                            <input type="text" id="stripe_public_key" name="stripe_public_key"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.stripe_public_key"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label for="stripe_secret_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz tajny</label>
                            <input type="password" id="stripe_secret_key" name="stripe_secret_key"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.stripe_secret_key"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                       x-model="settings.stripe_test_mode"
                                       @change="markAsChanged($event)">
                                <span class="ml-2 text-sm text-gray-700">Tryb testowy</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PayPal Settings -->
            <div x-show="settings.payment_gateway_primary === 'paypal'" x-transition>
                <div class="mt-4 p-3 bg-gray-100 rounded-md border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Ustawienia PayPal</h4>
                    <div class="space-y-3">
                        <div>
                            <label for="paypal_client_id" class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                            <input type="text" id="paypal_client_id" name="paypal_client_id"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.paypal_client_id"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label for="paypal_secret" class="block text-sm font-medium text-gray-700 mb-1">Secret</label>
                            <input type="password" id="paypal_secret" name="paypal_secret"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.paypal_secret"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                       x-model="settings.paypal_sandbox_mode"
                                       @change="markAsChanged($event)">
                                <span class="ml-2 text-sm text-gray-700">Tryb Sandbox</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Przelewy24 Settings -->
            <div x-show="settings.payment_gateway_primary === 'przelewy24'" x-transition>
                <div class="mt-4 p-3 bg-gray-100 rounded-md border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Ustawienia Przelewy24</h4>
                    <div class="space-y-3">
                        <div>
                            <label for="p24_merchant_id" class="block text-sm font-medium text-gray-700 mb-1">ID Sprzedawcy</label>
                            <input type="text" id="p24_merchant_id" name="p24_merchant_id"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.p24_merchant_id"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label for="p24_pos_id" class="block text-sm font-medium text-gray-700 mb-1">ID POS</label>
                            <input type="text" id="p24_pos_id" name="p24_pos_id"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.p24_pos_id"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label for="p24_crc_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz CRC</label>
                            <input type="password" id="p24_crc_key" name="p24_crc_key"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.p24_crc_key"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                       x-model="settings.p24_test_mode"
                                       @change="markAsChanged($event)">
                                <span class="ml-2 text-sm text-gray-700">Tryb testowy</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maps Integration -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Integracja Map</h3>
        <div class="space-y-4">
            <div>
                <label for="maps_provider" class="block text-sm font-medium text-gray-700 mb-1">Dostawca Map</label>
                <select id="maps_provider" name="maps_provider"
                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                        x-model="settings.maps_provider"
                        @change="markAsChanged($event)">
                    <option value="google">Google Maps</option>
                    <option value="mapbox">Mapbox</option>
                    <option value="openstreetmap">OpenStreetMap</option>
                </select>
            </div>
            
            <!-- Google Maps Settings -->
            <div x-show="settings.maps_provider === 'google'" x-transition>
                <div>
                    <label for="google_maps_api_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz API Google Maps</label>
                    <input type="password" id="google_maps_api_key" name="google_maps_api_key"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.google_maps_api_key"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <!-- Mapbox Settings -->
            <div x-show="settings.maps_provider === 'mapbox'" x-transition>
                <div>
                    <label for="mapbox_access_token" class="block text-sm font-medium text-gray-700 mb-1">Token dostępu Mapbox</label>
                    <input type="password" id="mapbox_access_token" name="mapbox_access_token"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.mapbox_access_token"
                           @change="markAsChanged($event)">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Social Media Integration -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Integracja z mediami społecznościowymi</h3>
        <div class="space-y-4">
            <!-- Social Login -->
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Logowanie przez social media</h4>
                <div class="space-y-3">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                   x-model="settings.social_login_fb_enabled"
                                   @change="markAsChanged($event)">
                            <span class="ml-2 text-sm text-gray-700">Logowanie przez Facebook</span>
                        </label>
                    </div>
                    <div x-show="settings.social_login_fb_enabled">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="facebook_app_id" class="block text-sm font-medium text-gray-700 mb-1">Facebook App ID</label>
                                <input type="text" id="facebook_app_id" name="facebook_app_id"
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="settings.facebook_app_id"
                                       @change="markAsChanged($event)">
                            </div>
                            <div>
                                <label for="facebook_app_secret" class="block text-sm font-medium text-gray-700 mb-1">Facebook App Secret</label>
                                <input type="password" id="facebook_app_secret" name="facebook_app_secret"
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="settings.facebook_app_secret"
                                       @change="markAsChanged($event)">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                   x-model="settings.social_login_google_enabled"
                                   @change="markAsChanged($event)">
                            <span class="ml-2 text-sm text-gray-700">Logowanie przez Google</span>
                        </label>
                    </div>
                    <div x-show="settings.social_login_google_enabled">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="google_client_id" class="block text-sm font-medium text-gray-700 mb-1">Google Client ID</label>
                                <input type="text" id="google_client_id" name="google_client_id"
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="settings.google_client_id"
                                       @change="markAsChanged($event)">
                            </div>
                            <div>
                                <label for="google_client_secret" class="block text-sm font-medium text-gray-700 mb-1">Google Client Secret</label>
                                <input type="password" id="google_client_secret" name="google_client_secret"
                                       class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="settings.google_client_secret"
                                       @change="markAsChanged($event)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Sharing -->
            <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Udostępnianie w social media</h4>
                <div class="space-y-3">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                   x-model="settings.enable_social_sharing"
                                   @change="markAsChanged($event)">
                            <span class="ml-2 text-sm text-gray-700">Włącz przyciski udostępniania</span>
                        </label>
                    </div>
                    <div x-show="settings.enable_social_sharing">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                           x-model="settings.share_facebook"
                                           @change="markAsChanged($event)">
                                    <span class="ml-2 text-sm text-gray-700">Facebook</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                           x-model="settings.share_twitter"
                                           @change="markAsChanged($event)">
                                    <span class="ml-2 text-sm text-gray-700">Twitter</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                           x-model="settings.share_whatsapp"
                                           @change="markAsChanged($event)">
                                    <span class="ml-2 text-sm text-gray-700">WhatsApp</span>
                                </label>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                           x-model="settings.share_email"
                                           @change="markAsChanged($event)">
                                    <span class="ml-2 text-sm text-gray-700">Email</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Integrations -->
    <div class="bg-gray-50 p-4 rounded-md">
        <h3 class="font-medium text-gray-700 mb-3">Integracje API</h3>
        <div class="space-y-4">
            <!-- Google Analytics -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_google_analytics"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Google Analytics</span>
                </label>
                
                <div x-show="settings.enable_google_analytics" class="mt-2">
                    <label for="google_analytics_id" class="block text-sm font-medium text-gray-700 mb-1">ID śledzenia</label>
                    <input type="text" id="google_analytics_id" name="google_analytics_id"
                           class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           x-model="settings.google_analytics_id"
                           @change="markAsChanged($event)">
                </div>
            </div>
            
            <!-- Mailchimp -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_mailchimp"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Mailchimp</span>
                </label>
                
                <div x-show="settings.enable_mailchimp" class="mt-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="mailchimp_api_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz API</label>
                            <input type="password" id="mailchimp_api_key" name="mailchimp_api_key"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.mailchimp_api_key"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label for="mailchimp_list_id" class="block text-sm font-medium text-gray-700 mb-1">ID listy</label>
                            <input type="text" id="mailchimp_list_id" name="mailchimp_list_id"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.mailchimp_list_id"
                                   @change="markAsChanged($event)">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- reCAPTCHA -->
            <div>
                <label class="flex items-center">
                    <input type="checkbox" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                           x-model="settings.enable_recaptcha"
                           @change="markAsChanged($event)">
                    <span class="ml-2 text-sm text-gray-700">Google reCAPTCHA</span>
                </label>
                
                <div x-show="settings.enable_recaptcha" class="mt-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="recaptcha_site_key" class="block text-sm font-medium text-gray-700 mb-1">Klucz witryny</label>
                            <input type="text" id="recaptcha_site_key" name="recaptcha_site_key"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.recaptcha_site_key"
                                   @change="markAsChanged($event)">
                        </div>
                        <div>
                            <label for="recaptcha_secret_key" class="block text-sm font-medium text-gray-700 mb-1">Tajny klucz</label>
                            <input type="password" id="recaptcha_secret_key" name="recaptcha_secret_key"
                                   class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="settings.recaptcha_secret_key"
                                   @change="markAsChanged($event)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="button" 
                @click="saveTabSettings('integrations')"
                class="settings-save hidden items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="isSaving || hasValidationErrors()"
                :class="{'opacity-75 cursor-wait': isSaving, 'opacity-50 cursor-not-allowed': hasValidationErrors()}">
            <span x-show="!isSaving">Zapisz ustawienia integracji</span>
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
