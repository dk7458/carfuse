<?php
/**
 * Admin Settings Page
 * Unified settings management interface for CarFuse administrators
 */

// Use standard authentication check component with admin role requirement
$required_role = 'admin';
$api_role_check = true; // Force API-based role verification for stronger security
$auth_redirect = '/auth/login';
$show_messages = true;
include_once BASE_PATH . '/public/views/components/auth-check.php';

// Set page title and meta description
$pageTitle = 'CarFuse - Ustawienia Systemu';
$metaDescription = 'Panel administracyjny CarFuse - zarządzanie ustawieniami systemu';

// Custom styles for settings page
$extraStyles = '
    .settings-form:has(.is-changed) .settings-save { display: flex; }
    .input-error { border-color: #ef4444 !important; }
    .fade-in { animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
';

// Start output buffering to capture the main content
ob_start();

// Generate a CSRF token for form submissions
$csrf_token = csrf_token();
?>

<div class="container mx-auto px-4 py-8" 
     x-data="settingsManager()"
     x-init="init()">
     
    <!-- Page Header -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Ustawienia Systemu</h1>
                <p class="text-gray-600">Zarządzaj konfiguracją systemu CarFuse</p>
            </div>
            <div class="mt-4 md:mt-0">
                <button @click="saveAllSettings()" 
                        class="settings-save hidden items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md" 
                        :class="{'opacity-50 cursor-wait': isSaving}">
                    <span x-show="!isSaving"><i class="fas fa-save mr-2"></i> Zapisz wszystkie zmiany</span>
                    <span x-show="isSaving" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Zapisywanie...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Success/error alert messages -->
    <div x-show="successMessage" x-transition.opacity class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded mb-6 flex justify-between items-center fade-in">
        <span><i class="fas fa-check-circle mr-2"></i> <span x-text="successMessage"></span></span>
        <button @click="successMessage = ''" class="text-green-700 hover:text-green-900">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div x-show="errorMessage" x-transition.opacity class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded mb-6 flex justify-between items-center fade-in">
        <span><i class="fas fa-exclamation-circle mr-2"></i> <span x-text="errorMessage"></span></span>
        <button @click="errorMessage = ''" class="text-red-700 hover:text-red-900">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Settings Tabs -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex flex-wrap -mb-px">
                <template x-for="tab in tabs" :key="tab.id">
                    <button 
                        @click="activeTab = tab.id" 
                        :class="{'border-blue-500 text-blue-600': activeTab === tab.id}"
                        class="mr-2 py-4 px-4 font-medium text-sm border-b-2 border-transparent hover:border-gray-300 hover:text-gray-700 whitespace-nowrap">
                        <i :class="tab.icon + ' mr-1'"></i> <span x-text="tab.name"></span>
                    </button>
                </template>
            </nav>
        </div>

        <!-- Tab Content Container -->
        <div class="p-6">
            <!-- General Settings Tab -->
            <div x-show="activeTab === 'general'" class="settings-form" x-cloak>
                <h2 class="text-lg font-medium text-gray-800 mb-4">Ustawienia Ogólne</h2>
                
                <!-- Hidden htmx form for submitting this tab's settings -->
                <form 
                    id="general-settings-form"
                    hx-post="/admin/api/settings/general"
                    hx-ext="auth-helper"
                    hx-swap="outerHTML"
                    hx-indicator="#general-indicator"
                    @submit.prevent="submitTabSettings('general')">
                    
                    <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                    <?php include BASE_PATH . '/public/views/admin/settings/general-settings.php'; ?>
                </form>
                <div id="general-indicator" class="htmx-indicator">Zapisywanie...</div>
            </div>

            <!-- Booking Settings Tab -->
            <div x-show="activeTab === 'booking'" class="settings-form" x-cloak>
                <h2 class="text-lg font-medium text-gray-800 mb-4">Ustawienia Rezerwacji</h2>
                
                <!-- Hidden htmx form for submitting this tab's settings -->
                <form 
                    id="booking-settings-form"
                    hx-post="/admin/api/settings/booking" 
                    hx-ext="auth-helper"
                    hx-swap="outerHTML"
                    hx-indicator="#booking-indicator"
                    @submit.prevent="submitTabSettings('booking')">
                    
                    <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                    <?php include BASE_PATH . '/public/views/admin/settings/booking-settings.php'; ?>
                </form>
                <div id="booking-indicator" class="htmx-indicator">Zapisywanie...</div>
            </div>

            <!-- Notifications Settings Tab -->
            <div x-show="activeTab === 'notifications'" class="settings-form" x-cloak>
                <h2 class="text-lg font-medium text-gray-800 mb-4">Ustawienia Powiadomień</h2>
                
                <!-- Hidden htmx form for submitting this tab's settings -->
                <form 
                    id="notifications-settings-form"
                    hx-post="/admin/api/settings/notifications" 
                    hx-ext="auth-helper"
                    hx-swap="outerHTML"
                    hx-indicator="#notifications-indicator"
                    @submit.prevent="submitTabSettings('notifications')">
                    
                    <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                    <?php include BASE_PATH . '/public/views/admin/settings/notification-settings.php'; ?>
                </form>
                 <div id="notifications-indicator" class="htmx-indicator">Zapisywanie...</div>
            </div>

            <!-- Security Settings Tab -->
            <div x-show="activeTab === 'security'" class="settings-form" x-cloak>
                <h2 class="text-lg font-medium text-gray-800 mb-4">Ustawienia Bezpieczeństwa</h2>
                
                <!-- Hidden htmx form for submitting this tab's settings -->
                <form 
                    id="security-settings-form"
                    hx-post="/admin/api/settings/security" 
                    hx-ext="auth-helper"
                    hx-swap="outerHTML"
                    hx-indicator="#security-indicator"
                    @submit.prevent="submitTabSettings('security')">
                    
                    <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                    <?php include BASE_PATH . '/public/views/admin/settings/security-settings.php'; ?>
                </form>
                <div id="security-indicator" class="htmx-indicator">Zapisywanie...</div>
            </div>

            <!-- Integrations Settings Tab -->
            <div x-show="activeTab === 'integrations'" class="settings-form" x-cloak>
                <h2 class="text-lg font-medium text-gray-800 mb-4">Integracje</h2>
                
                <!-- Hidden htmx form for submitting this tab's settings -->
                <form 
                    id="integrations-settings-form"
                    hx-post="/admin/api/settings/integrations" 
                    hx-ext="auth-helper"
                    hx-swap="outerHTML"
                    hx-indicator="#integrations-indicator"
                    @submit.prevent="submitTabSettings('integrations')">
                    
                    <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                    <?php include BASE_PATH . '/public/views/admin/settings/integration-settings.php'; ?>
                </form>
                <div id="integrations-indicator" class="htmx-indicator">Zapisywanie...</div>
            </div>
        </div>
    </div>
    
    <!-- Email test form -->
    <form 
        id="test-email-form"
        hx-post="/admin/api/settings/test-email" 
        hx-ext="auth-helper"
        hx-swap="none"
        hx-trigger="none"
        @htmx:after-request="handleEmailTestResponse($event.detail)">
        
        <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
    </form>
    
</div>

<!-- Updated settings manager script with HTMX support -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('settingsManager', () => ({
        activeTab: 'general',
        settings: {},
        originalSettings: {},
        isSaving: false,
        testingConnection: false,
        successMessage: '',
        errorMessage: '',
        validationErrors: {},
        tabs: [
            { id: 'general', name: 'Ogólne', icon: 'fas fa-cog' },
            { id: 'booking', name: 'Rezerwacje', icon: 'fas fa-calendar-alt' },
            { id: 'notifications', name: 'Powiadomienia', icon: 'fas fa-bell' },
            { id: 'security', name: 'Bezpieczeństwo', icon: 'fas fa-shield-alt' },
            { id: 'integrations', name: 'Integracje', icon: 'fas fa-plug' }
        ],
        
        init() {
            this.fetchSettings();
            
            // Listen for HTMX responses
            document.body.addEventListener('htmx:beforeRequest', (event) => {
                this.isSaving = true;
                this.errorMessage = '';
                this.successMessage = '';
            });
            
            document.body.addEventListener('htmx:afterRequest', (event) => {
                this.isSaving = false;
            });
            
            document.body.addEventListener('htmx:responseError', (event) => {
                this.handleHtmxError(event);
            });
            
            // Auto-save settings on tab change if there are changes
            this.$watch('activeTab', (newTab, oldTab) => {
                if (this.hasChanges(oldTab) && !this.hasValidationErrors()) {
                    this.saveTabSettings(oldTab);
                }
            });
        },
        
        fetchSettings() {
            this.isSaving = true;
            
            // Use HTMX to fetch settings
            htmx.ajax('GET', '/admin/api/settings', {
                target: '#general-settings-form',
                swap: 'innerHTML',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(response => {
                try {
                    const data = JSON.parse(response.xhr.responseText);
                    if (data.status === 'success') {
                        this.settings = data.data;
                        this.originalSettings = JSON.parse(JSON.stringify(data.data));
                    } else {
                        this.errorMessage = data.message || 'Błąd podczas pobierania ustawień';
                    }
                } catch (e) {
                    console.error('Error parsing settings response:', e);
                    this.errorMessage = 'Błąd przetwarzania odpowiedzi serwera';
                } finally {
                    this.isSaving = false;
                }
            }).catch(error => {
                console.error('Error fetching settings:', error);
                this.errorMessage = 'Wystąpił błąd podczas pobierania ustawień';
                this.isSaving = false;
            });
        },
        
        handleHtmxError(event) {
            this.isSaving = false;
            try {
                const response = JSON.parse(event.detail.xhr.responseText);
                this.errorMessage = response.message || 'Wystąpił błąd podczas zapisywania ustawień';
                
                // Handle validation errors if present
                if (response.errors) {
                    this.validationErrors = response.errors;
                    
                    // Add error class to fields with validation errors
                    Object.keys(response.errors).forEach(field => {
                        document.getElementById(field)?.classList.add('input-error');
                    });
                }
            } catch (e) {
                this.errorMessage = 'Wystąpił błąd podczas przetwarzania odpowiedzi serwera';
            }
        },
        
        saveAllSettings() {
            if (this.hasValidationErrors()) {
                this.errorMessage = 'Popraw błędy walidacji przed zapisaniem';
                return;
            }
            
            // Prepare form data with all settings
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/api/settings';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf_token';
            csrfInput.value = document.querySelector('[name="_csrf_token"]').value;
            form.appendChild(csrfInput);
            
            // Add settings data
            Object.entries(this.settings).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = typeof value === 'object' ? JSON.stringify(value) : value;
                form.appendChild(input);
            });
            
            // Submit form using HTMX
            this.isSaving = true;
            
            htmx.ajax('POST', '/admin/api/settings', {
                source: form,
                swap: 'none',
                values: Object.fromEntries(new FormData(form)),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(response => {
                try {
                    const data = JSON.parse(response.xhr.responseText);
                    if (data.status === 'success') {
                        this.successMessage = 'Ustawienia zostały zapisane pomyślnie';
                        this.originalSettings = JSON.parse(JSON.stringify(this.settings));
                        
                        // Remove is-changed classes
                        document.querySelectorAll('.is-changed').forEach(el => {
                            el.classList.remove('is-changed');
                        });
                    } else {
                        this.errorMessage = data.message || 'Błąd podczas zapisywania ustawień';
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    this.errorMessage = 'Błąd przetwarzania odpowiedzi serwera';
                } finally {
                    this.isSaving = false;
                }
            }).catch(error => {
                console.error('Error saving all settings:', error);
                this.errorMessage = 'Wystąpił błąd podczas zapisywania ustawień';
                this.isSaving = false;
            });
        },
        
        submitTabSettings(tabName) {
            if (this.hasValidationErrors()) {
                this.errorMessage = 'Popraw błędy walidacji przed zapisaniem';
                return;
            }
            
            const form = document.getElementById(`${tabName}-settings-form`);
            
            // Submit the form using HTMX
            htmx.ajax('POST', `/admin/api/settings/${tabName}`, {
                target: `#${tabName}-settings-form`,
                swap: 'outerHTML',
                values: Object.fromEntries(new FormData(form)),
                 headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
        },
        
        saveTabSettings(tabName) {
            if (this.hasValidationErrors()) {
                this.errorMessage = 'Popraw błędy walidacji przed zapisaniem';
                return;
            }
            
            this.isSaving = true;
            this.errorMessage = '';
            
            // Extract only the settings relevant to this tab
            const tabSettings = {};
            Object.keys(this.settings).forEach(key => {
                if (key.startsWith(tabName + '_') || this.getTabForSetting(key) === tabName) {
                    tabSettings[key] = this.settings[key];
                }
            });
            
            // Use authenticated fetch with token
            fetch(`/admin/api/settings/${tabName}`, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(tabSettings)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.successMessage = 'Ustawienia zostały zapisane pomyślnie';
                    
                    // Update originalSettings for the saved tab
                    Object.keys(tabSettings).forEach(key => {
                        this.originalSettings[key] = tabSettings[key];
                    });
                    
                    // Remove is-changed class from tab's inputs
                    document.querySelectorAll(`[x-show="activeTab === '${tabName}'"] .is-changed`).forEach(el => {
                        el.classList.remove('is-changed');
                    });
                } else {
                    this.errorMessage = data.message || 'Błąd podczas zapisywania ustawień';
                }
            })
            .catch(error => {
                console.error(`Error saving ${tabName} settings:`, error);
                this.errorMessage = `Wystąpił błąd podczas zapisywania ustawień ${tabName}`;
            })
            .finally(() => {
                this.isSaving = false;
            });
        },
        
        markAsChanged(event) {
            event.target.classList.add('is-changed');
            this.validateField(event.target.name, event.target.value);
        },
        
        hasChanges(tabName) {
            return Object.keys(this.settings).some(key => {
                return (this.getTabForSetting(key) === tabName) && 
                       JSON.stringify(this.settings[key]) !== JSON.stringify(this.originalSettings[key]);
            });
        },
        
        hasValidationErrors() {
            return Object.keys(this.validationErrors).length > 0;
        },
        
        getTabForSetting(key) {
            // Map setting keys to their respective tabs
            const tabMappings = {
                'company_': 'general',
                'site_': 'general',
                'meta_': 'general',
                'tax_': 'general',
                'currency': 'general',
                'booking_': 'booking',
                'rental_': 'booking',
                'deposit': 'booking',
                'buffer_time': 'booking',
                'cancellation_': 'booking',
                'pickup_': 'booking',
                'dropoff_': 'booking',
                'pricing': 'booking',
                'email_': 'notifications',
                'smtp_': 'notifications',
                'sms_': 'notifications',
                'notify_': 'notifications',
                'password_': 'security',
                'login_': 'security',
                'session_': 'security',
                'maintenance_': 'security',
                'captcha_': 'security',
                'api_': 'integrations',
                'payment_gateway': 'integrations',
                'maps_': 'integrations'
            };
            
            for (const prefix in tabMappings) {
                if (key.startsWith(prefix)) {
                    return tabMappings[prefix];
                }
            }
            
            return 'general'; // Default tab
        },
        
        validateField(field, value) {
            // Clear previous error for this field
            delete this.validationErrors[field];
            
            // Validation rules
            const validations = {
                'email_sender': (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) ? null : 'Niepoprawny format emaila',
                'smtp_host': (val) => val.length > 0 ? null : 'Pole jest wymagane',
                'smtp_port': (val) => !isNaN(val) && val > 0 && val <= 65535 ? null : 'Niepoprawny port',
                'tax_rate': (val) => !isNaN(val) && val >= 0 && val <= 100 ? null : 'Wartość musi być między 0 a 100',
                'buffer_time': (val) => !isNaN(val) && val >= 0 ? null : 'Wartość musi być nieujemna',
                'min_rental_period': (val) => !isNaN(val) && val > 0 ? null : 'Wartość musi być większa od 0',
                'max_rental_period': (val) => !isNaN(val) && val > 0 ? null : 'Wartość musi być większa od 0'
            };
            
            // Run validation if exists for this field
            if (validations[field]) {
                const error = validations[field](value);
                if (error) {
                    this.validationErrors[field] = error;
                    // Add error class to the field
                    document.getElementById(field)?.classList.add('input-error');
                } else {
                    // Remove error class
                    document.getElementById(field)?.classList.remove('input-error');
                }
            }
            
            return !this.validationErrors[field];
        },
        
        // New method to test email connection
        testEmailConnection() {
            // Validate required email fields before testing
            const requiredFields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'email_sender'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!this.settings[field]) {
                    this.validationErrors[field] = 'To pole jest wymagane do testu połączenia';
                    document.getElementById(field)?.classList.add('input-error');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                this.errorMessage = 'Uzupełnij wszystkie wymagane pola SMTP przed testem';
                return;
            }
            
            this.testingConnection = true;
            this.errorMessage = '';
            this.successMessage = '';
            
            // Use authenticated fetch with token
            fetch('/admin/api/settings/test-email', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    smtp_host: this.settings.smtp_host,
                    smtp_port: this.settings.smtp_port, 
                    smtp_username: this.settings.smtp_username,
                    smtp_password: this.settings.smtp_password,
                    email_encryption: this.settings.email_encryption || 'tls',
                    email_sender: this.settings.email_sender
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.successMessage = 'Test połączenia email zakończony powodzeniem. Wiadomość wysłana.';
                } else {
                    this.errorMessage = 'Test połączenia nieudany: ' + (data.message || 'Nieznany błąd');
                }
            })
            .catch(error => {
                console.error('Error testing email connection:', error);
                this.errorMessage = 'Wystąpił błąd podczas testowania połączenia email';
            })
            .finally(() => {
                this.testingConnection = false;
            });
        },
         handleEmailTestResponse(detail) {
            this.testingConnection = false;
            if (detail.successful) {
                try {
                    const response = JSON.parse(detail.xhr.responseText);
                    if (response.status === 'success') {
                        this.successMessage = 'Test połączenia email zakończony powodzeniem. Wiadomość wysłana.';
                    } else {
                        this.errorMessage = 'Test połączenia nieudany: ' + (response.message || 'Nieznany błąd');
                    }
                } catch (e) {
                    console.error('Error parsing email test response:', e);
                    this.errorMessage = 'Błąd przetwarzania odpowiedzi serwera';
                }
            } else {
                this.errorMessage = 'Wystąpił błąd podczas testowania połączenia email';
            }
        }
    }));
});
</script>

<?php
// Capture the content
$content = ob_get_clean();

// Include the base layout
include BASE_PATH . '/public/views/layouts/base.php';
?>
