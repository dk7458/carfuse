/**
 * CarFuse Storage Component
 * Unified API for client-side data persistence with localStorage, sessionStorage, and IndexedDB
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Check if Storage is already initialized
    if (CarFuse.storage) {
        console.warn('CarFuse Storage component already initialized.');
        return;
    }
    
    // Define the component
    const storageComponent = {
        // Configuration
        config: {
            defaultNamespace: 'carfuse',
            useEncryption: false,
            defaultExpiration: 86400, // 24 hours in seconds
            storagePriority: ['localStorage', 'sessionStorage'], // IndexedDB not implemented
            debug: false
        },
        
        // State
        state: {
            initialized: false,
            storageInstances: {},
            isQuotaExceeded: false
        },
        
        /**
         * Initialize Storage functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Storage component');
            this.initializeStorageInstances();
            this.log('Storage component initialized');
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (CarFuse.config.debug) {
                console.log(`[CarFuse Storage] ${message}`, data || '');
            }
        },
        
        /**
         * Initialize storage instances based on priority
         */
        initializeStorageInstances: function() {
            this.config.storagePriority.forEach(type => {
                try {
                    if (type === 'localStorage' && typeof localStorage !== 'undefined') {
                        this.state.storageInstances.localStorage = localStorage;
                        this.log('localStorage initialized');
                    } else if (type === 'sessionStorage' && typeof sessionStorage !== 'undefined') {
                        this.state.storageInstances.sessionStorage = sessionStorage;
                        this.log('sessionStorage initialized');
                    }
                } catch (e) {
                    console.warn(`Failed to initialize ${type}`, e);
                    if (e.name === 'QuotaExceededError') {
                        this.state.isQuotaExceeded = true;
                    }
                }
            });
        },
        
        /**
         * Set data in storage
         * @param {string} key - Key to store data under
         * @param {*} value - Data to store (must be serializable)
         * @param {object} options - Storage options (namespace, expiration, storageType)
         */
        setItem: function(key, value, options = {}) {
            const namespace = options.namespace || this.config.defaultNamespace;
            const storageType = options.storageType || this.config.storagePriority[0];
            const expiration = options.expiration || this.config.defaultExpiration;
            
            // Check if storage type is available
            if (!this.state.storageInstances[storageType]) {
                console.warn(`Storage type ${storageType} not available`);
                return false;
            }
            
            try {
                let data = {
                    value: value,
                    expiry: Date.now() + (expiration * 1000)
                };
                
                if (this.config.useEncryption) {
                    // Implement encryption logic here
                    console.warn('Encryption not yet implemented');
                }
                
                const namespacedKey = `${namespace}_${key}`;
                this.state.storageInstances[storageType].setItem(namespacedKey, JSON.stringify(data));
                this.log(`setItem: ${namespacedKey} in ${storageType}`);
                return true;
            } catch (e) {
                console.error(`Failed to set item in ${storageType}`, e);
                if (e.name === 'QuotaExceededError') {
                    this.state.isQuotaExceeded = true;
                    console.warn('Storage quota exceeded');
                }
                return false;
            }
        },
        
        /**
         * Get data from storage
         * @param {string} key - Key to retrieve data from
         * @param {object} options - Storage options (namespace, storageType)
         * @returns {*} Retrieved data or null if not found or expired
         */
        getItem: function(key, options = {}) {
            const namespace = options.namespace || this.config.defaultNamespace;
            const storageType = options.storageType || this.config.storagePriority[0];
            
            // Check if storage type is available
            if (!this.state.storageInstances[storageType]) {
                console.warn(`Storage type ${storageType} not available`);
                return null;
            }
            
            try {
                const namespacedKey = `${namespace}_${key}`;
                const itemStr = this.state.storageInstances[storageType].getItem(namespacedKey);
                
                if (!itemStr) {
                    return null;
                }
                
                const item = JSON.parse(itemStr);
                
                if (item.expiry && Date.now() > item.expiry) {
                    // Item has expired, remove it
                    this.removeItem(key, options);
                    return null;
                }
                
                let value = item.value;
                
                if (this.config.useEncryption) {
                    // Implement decryption logic here
                    console.warn('Decryption not yet implemented');
                }
                
                this.log(`getItem: ${namespacedKey} from ${storageType}`);
                return value;
            } catch (e) {
                console.error(`Failed to get item from ${storageType}`, e);
                return null;
            }
        },
        
        /**
         * Remove data from storage
         * @param {string} key - Key to remove
         * @param {object} options - Storage options (namespace, storageType)
         */
        removeItem: function(key, options = {}) {
            const namespace = options.namespace || this.config.defaultNamespace;
            const storageType = options.storageType || this.config.storagePriority[0];
            
            // Check if storage type is available
            if (!this.state.storageInstances[storageType]) {
                console.warn(`Storage type ${storageType} not available`);
                return;
            }
            
            try {
                const namespacedKey = `${namespace}_${key}`;
                this.state.storageInstances[storageType].removeItem(namespacedKey);
                this.log(`removeItem: ${namespacedKey} from ${storageType}`);
            } catch (e) {
                console.error(`Failed to remove item from ${storageType}`, e);
            }
        },
        
        /**
         * Clear all data from a specific namespace
         * @param {string} namespace - Namespace to clear
         * @param {string} storageType - Storage type to clear
         */
        clearNamespace: function(namespace, storageType) {
            if (!this.state.storageInstances[storageType]) {
                console.warn(`Storage type ${storageType} not available`);
                return;
            }
            
            try {
                for (let i = 0; i < this.state.storageInstances[storageType].length; i++) {
                    const key = this.state.storageInstances[storageType].key(i);
                    if (key.startsWith(namespace + '_')) {
                        this.state.storageInstances[storageType].removeItem(key);
                    }
                }
                this.log(`clearNamespace: ${namespace} from ${storageType}`);
            } catch (e) {
                console.error(`Failed to clear namespace from ${storageType}`, e);
            }
        },
        
        /**
         * Clear all data from all namespaces
         */
        clearAll: function() {
            for (const storageType in this.state.storageInstances) {
                try {
                    this.state.storageInstances[storageType].clear();
                    this.log(`clearAll: ${storageType}`);
                } catch (e) {
                    console.error(`Failed to clear all from ${storageType}`, e);
                }
            }
        },
        
        /**
         * Check if storage quota has been exceeded
         * @returns {boolean} True if quota exceeded
         */
        isQuotaExceeded: function() {
            return this.state.isQuotaExceeded;
        },
        
        /**
         * Migrate data from one schema to another
         * @param {string} oldKey - Old key
         * @param {string} newKey - New key
         * @param {function} transform - Transformation function
         */
        migrateData: function(oldKey, newKey, transform) {
            const data = this.getItem(oldKey);
            if (data) {
                const transformedData = transform(data);
                this.setItem(newKey, transformedData);
                this.removeItem(oldKey);
                this.log(`Migrated data from ${oldKey} to ${newKey}`);
            }
        },
        
        /**
         * Purge all user-related data for GDPR compliance
         * @param {string} userId - User ID
         */
        purgeUserData: function(userId) {
            // Clear all data associated with the user ID
            // This might involve iterating through all storage instances and removing keys
            // that contain the user ID
            this.log(`Purging user data for user ID: ${userId}`);
            
            // Example: Clear all keys that start with 'user_' + userId
            for (const storageType in this.state.storageInstances) {
                try {
                    for (let i = 0; i < this.state.storageInstances[storageType].length; i++) {
                        const key = this.state.storageInstances[storageType].key(i);
                        if (key.startsWith('user_' + userId)) {
                            this.state.storageInstances[storageType].removeItem(key);
                        }
                    }
                    this.log(`Purged user data from ${storageType}`);
                } catch (e) {
                    console.error(`Failed to purge user data from ${storageType}`, e);
                }
            }
        }
    };
    
    // Register the component
    CarFuse.storage = storageComponent;

    // Initialize the component if CarFuse is ready
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent('storage', CarFuse.storage);
    } else {
        console.warn('CarFuse.registerComponent is not available. Make sure core.js is loaded first.');
    }
})();
