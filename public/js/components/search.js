/**
 * CarFuse Search Component
 * Provides enhanced search functionality with type-ahead suggestions and fuzzy matching
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Check if Search is already initialized
    if (CarFuse.search) {
        console.warn('CarFuse Search component already initialized.');
        return;
    }
    
    // Define the component
    const searchComponent = {
        // Configuration
        config: {
            endpoint: '/api/search',
            debounceDelay: 300,
            minChars: 3,
            maxResults: 10,
            searchHistorySize: 5,
            debug: false
        },
        
        // State
        state: {
            initialized: false,
            searchHistory: [],
            currentResults: [],
            searchActive: false
        },
        
        /**
         * Initialize Search functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Search component');
            this.loadSearchHistory();
            this.setupEventListeners();
            this.state.initialized = true;
            this.log('Search component initialized');
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (CarFuse.config.debug) {
                console.log(`[CarFuse Search] ${message}`, data || '');
            }
        },
        
        /**
         * Load search history from storage
         */
        loadSearchHistory: function() {
            const history = CarFuse.storage.getItem('searchHistory', { namespace: 'search' }) || [];
            this.state.searchHistory = history;
            this.log('Loaded search history', this.state.searchHistory);
        },
        
        /**
         * Save search history to storage
         */
        saveSearchHistory: function() {
            CarFuse.storage.setItem('searchHistory', this.state.searchHistory, { namespace: 'search' });
            this.log('Saved search history', this.state.searchHistory);
        },
        
        /**
         * Add a search term to the search history
         * @param {string} term - Search term to add
         */
        addSearchHistory: function(term) {
            if (!term) return;
            
            // Remove if already exists
            const index = this.state.searchHistory.indexOf(term);
            if (index > -1) {
                this.state.searchHistory.splice(index, 1);
            }
            
            // Add to the beginning
            this.state.searchHistory.unshift(term);
            
            // Limit the size
            if (this.state.searchHistory.length > this.config.searchHistorySize) {
                this.state.searchHistory = this.state.searchHistory.slice(0, this.config.searchHistorySize);
            }
            
            this.saveSearchHistory();
        },
        
        /**
         * Clear search history
         */
        clearSearchHistory: function() {
            this.state.searchHistory = [];
            this.saveSearchHistory();
            this.log('Cleared search history');
        },
        
        /**
         * Setup event listeners for search input
         */
        setupEventListeners: function() {
            this.log('Setting up search event listeners');
            
            const searchInput = document.getElementById('search-input');
            if (!searchInput) {
                console.warn('Search input not found');
                return;
            }
            
            // Debounce search input
            const debouncedSearch = this.debounce((e) => {
                const term = e.target.value;
                if (term.length >= this.config.minChars) {
                    this.performSearch(term);
                } else {
                    this.clearResults();
                }
            }, this.config.debounceDelay);
            
            searchInput.addEventListener('input', debouncedSearch);
            
            // Handle clear button
            const clearButton = document.getElementById('search-clear');
            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    searchInput.value = '';
                    this.clearResults();
                });
            }
            
            // Handle focus and blur
            searchInput.addEventListener('focus', () => {
                this.state.searchActive = true;
            });
            
            searchInput.addEventListener('blur', () => {
                setTimeout(() => {
                    this.state.searchActive = false;
                }, 200); // Small delay to allow click on results
            });
        },
        
        /**
         * Perform search
         * @param {string} term - Search term
         */
        performSearch: function(term) {
            this.log(`Performing search for term: ${term}`);
            
            // Show loading indicator
            CarFuse.loader.showLoadingIndicator(document.getElementById('search-container'));
            
            // Call API to get search results
            CarFuse.api.fetch(`${this.config.endpoint}?term=${encodeURIComponent(term)}`)
                .then(data => {
                    // Hide loading indicator
                    CarFuse.loader.hideLoadingIndicator(document.getElementById('search-container'));
                    
                    // Process results
                    this.state.currentResults = data.results || [];
                    this.displayResults(this.state.currentResults);
                    
                    // Add to search history
                    this.addSearchHistory(term);
                })
                .catch(error => {
                    // Hide loading indicator
                    CarFuse.loader.hideLoadingIndicator(document.getElementById('search-container'));
                    
                    console.error('Search failed', error);
                    CarFuse.notifications.showToast('Błąd', 'Wystąpił błąd podczas wyszukiwania', 'error');
                });
        },
        
        /**
         * Display search results
         * @param {Array} results - Search results
         */
        displayResults: function(results) {
            const resultsContainer = document.getElementById('search-results');
            if (!resultsContainer) {
                console.warn('Search results container not found');
                return;
            }
            
            // Clear existing results
            resultsContainer.innerHTML = '';
            
            if (results.length === 0) {
                resultsContainer.textContent = 'Brak wyników.';
                return;
            }
            
            // Create result elements
            results.forEach(result => {
                const resultEl = document.createElement('div');
                resultEl.className = 'search-result-item';
                resultEl.textContent = result.title || result.name || 'Unknown';
                resultsContainer.appendChild(resultEl);
            });
        },
        
        /**
         * Clear search results
         */
        clearResults: function() {
            this.state.currentResults = [];
            const resultsContainer = document.getElementById('search-results');
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
            }
        },
        
        /**
         * Throttle a function
         * @param {Function} func - Function to throttle
         * @param {number} limit - Time limit in milliseconds
         * @returns {Function} Throttled function
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const context = this;
                const args = arguments;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },
        
        /**
         * Debounce a function
         * @param {Function} func - Function to debounce
         * @param {number} delay - Delay in milliseconds
         * @returns {Function} Debounced function
         */
        debounce: function(func, delay) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        }
    };
    
    // Register the component
    CarFuse.search = searchComponent;

    // Initialize the component if CarFuse is ready
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent('search', CarFuse.search);
    } else {
        console.warn('CarFuse.registerComponent is not available. Make sure core.js is loaded first.');
    }
})();
