/**
 * CarFuse i18n Component
 * Provides internationalization and localization support
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        console.error('CarFuse global object is not defined.');
        return;
    }
    
    const CarFuse = window.CarFuse;
    
    // Check if i18n is already initialized
    if (CarFuse.i18n) {
        console.warn('CarFuse i18n component already initialized.');
        return;
    }
    
    CarFuse.i18n = {
        // Configuration
        config: {
            defaultLocale: 'pl-PL',
            availableLocales: ['pl-PL', 'en-US'],
            fallbackLocale: 'en-US',
            debug: false
        },
        
        // State
        state: {
            initialized: false,
            currentLocale: null,
            translations: {}
        },
        
        /**
         * Initialize i18n functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing i18n component');
            
            // Set current locale
            this.setCurrentLocale(this.config.defaultLocale);
            
            // Load translations
            this.loadTranslations(this.state.currentLocale)
                .then(() => {
                    this.state.initialized = true;
                    this.log('i18n component initialized');
                })
                .catch(error => {
                    console.error('Failed to initialize i18n', error);
                });
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (CarFuse.config.debug) {
                console.log(`[CarFuse i18n] ${message}`, data || '');
            }
        },
        
        /**
         * Set the current locale
         * @param {string} locale - Locale code (e.g., 'en-US', 'pl-PL')
         */
        setCurrentLocale: function(locale) {
            if (!this.config.availableLocales.includes(locale)) {
                console.warn(`Locale ${locale} not available, falling back to ${this.config.fallbackLocale}`);
                locale = this.config.fallbackLocale;
            }
            
            this.state.currentLocale = locale;
            this.log(`Current locale set to ${locale}`);
        },
        
        /**
         * Load translations for a specific locale
         * @param {string} locale - Locale code
         * @returns {Promise} Promise resolving when translations are loaded
         */
        loadTranslations: function(locale) {
            return new Promise((resolve, reject) => {
                // Check if translations are already loaded
                if (this.state.translations[locale]) {
                    this.log(`Translations for ${locale} already loaded`);
                    resolve();
                    return;
                }
                
                // Load translations from a JSON file
                const translationFile = `/i18n/${locale}.json`;
                
                fetch(translationFile)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Failed to load translations for ${locale}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        this.state.translations[locale] = data;
                        this.log(`Translations for ${locale} loaded`);
                        resolve();
                    })
                    .catch(error => {
                        console.error(`Failed to load translations for ${locale}`, error);
                        reject(error);
                    });
            });
        },
        
        /**
         * Translate a key
         * @param {string} key - Translation key
         * @param {object} [params] - Optional parameters for translation
         * @returns {string} Translated string or the key if not found
         */
        translate: function(key, params = {}) {
            const locale = this.state.currentLocale;
            const translations = this.state.translations[locale] || {};
            
            let translation = translations[key] || key;
            
            // Replace parameters in translation
            for (const param in params) {
                translation = translation.replace(`{${param}}`, params[param]);
            }
            
            return translation;
        },
        
        /**
         * Format a number according to the current locale
         * @param {number} number - Number to format
         * @param {object} [options] - Formatting options
         * @returns {string} Formatted number
         */
        formatNumber: function(number, options = {}) {
            return new Intl.NumberFormat(this.state.currentLocale, options).format(number);
        },
        
        /**
         * Format a currency according to the current locale
         * @param {number} number - Number to format
         * @param {string} [currency] - Currency code
         * @returns {string} Formatted currency
         */
        formatCurrency: function(number, currency = 'PLN') {
            return new Intl.NumberFormat(this.state.currentLocale, {
                style: 'currency',
                currency: currency
            }).format(number);
        },
        
        /**
         * Get the plural form of a word based on the current locale
         * @param {number} count - Number to determine plural form
         * @param {object} forms - Object with plural forms (e.g., { one: 'jeden', few: 'kilka', many: 'wiele', other: 'inne' })
         * @returns {string} Plural form of the word
         */
        getPluralForm: function(count, forms) {
            // Implement Polish pluralization rules
            const count100 = count % 100;
            const count10 = count % 10;
            
            if (count === 1) {
                return forms.one || forms.other;
            }
            
            if (count10 >= 2 && count10 <= 4 && (count100 < 10 || count100 >= 20)) {
                return forms.few || forms.other;
            }
            
            if (count10 === 0 || count10 > 4 || (count100 >= 10 && count100 <= 20)) {
                return forms.many || forms.other;
            }
            
            return forms.other;
        },
        
        /**
         * Get the localized address format
         * @param {object} address - Address object
         * @returns {string} Formatted address
         */
        formatAddress: function(address) {
            // Implement Polish address format
            return `${address.street}\n${address.postalCode} ${address.city}\n${address.country}`;
        },
        
        /**
         * Format a phone number according to the current locale
         * @param {string} phoneNumber - Phone number to format
         * @returns {string} Formatted phone number
         */
        formatPhoneNumber: function(phoneNumber) {
            // Implement Polish phone number format
            return phoneNumber.replace(/(\d{3})(\d{3})(\d{3})/, '$1-$2-$3');
        },
        
        /**
         * Validate a Polish PESEL number
         * @param {string} pesel - PESEL number to validate
         * @returns {boolean} True if PESEL is valid
         */
        validatePESEL: function(pesel) {
            // Implement PESEL validation logic
            if (typeof PeselValidator !== 'undefined' && PeselValidator.isValid(pesel)) {
                return true;
            }
            return false;
        },
        
        /**
         * Validate a Polish NIP number
         * @param {string} nip - NIP number to validate
         * @returns {boolean} True if NIP is valid
         */
        validateNIP: function(nip) {
            // Implement NIP validation logic
            if (typeof NIPValidator !== 'undefined' && NIPValidator.isValid(nip)) {
                return true;
            }
            return false;
        }
    };
    
    // Initialize i18n component
    CarFuse.i18n.init();
})();
