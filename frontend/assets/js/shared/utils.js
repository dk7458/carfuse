/**
 * File: /assets/js/shared/utils.js
 * Description: Enhanced utilities for API handling, error management, and loading states
 * Changelog:
 * - Added CSRF token handling
 * - Added global loading state management
 * - Enhanced error handling with toast notifications
 */

/**
 * Global loading state handler
 */
const loadingState = {
    count: 0,
    loaderElement: null,
    
    init() {
        if (!this.loaderElement) {
            this.loaderElement = document.createElement('div');
            this.loaderElement.className = 'global-loader hidden';
            this.loaderElement.innerHTML = `
                <div class="loader-spinner"></div>
                <div class="loader-text">Loading...</div>
            `;
            document.body.appendChild(this.loaderElement);
        }
    },

    show() {
        this.count++;
        if (this.count === 1) {
            this.loaderElement?.classList.remove('hidden');
        }
    },

    hide() {
        this.count = Math.max(0, this.count - 1);
        if (this.count === 0) {
            this.loaderElement?.classList.add('hidden');
        }
    }
};

/**
 * Handle API errors and display appropriate messages
 * @param {Error} error - The error object
 * @param {string} context - Context where the error occurred
 */
function handleApiError(error, context = '') {
    console.error(`API Error ${context ? `(${context})` : ''}:`, error);

    let message = 'An unexpected error occurred.';
    if (error.response) {
        message = error.response.data?.message || error.response.statusText;
    } else if (error.message) {
        message = error.message;
    }

    // Show toast notification
    const toast = document.createElement('div');
    toast.className = 'toast-notification error';
    toast.innerHTML = `
        <div class="toast-header">
            <strong>Error</strong>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
        <div class="toast-body">${message}</div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);

    throw error;
}

/**
 * Enhanced fetch data function with CSRF and loading state handling
 * @param {string} endpoint - API endpoint
 * @param {object} options - Request options
 * @returns {Promise<any>} Response data
 */
async function fetchData(endpoint, options = {}) {
    const {
        method = 'GET',
        body = null,
        params = null,
        showLoader: shouldShowLoader = true
    } = options;

    try {
        // Show loader if requested
        if (shouldShowLoader) {
            loadingState.show();
        }

        // Build request URL with params
        const url = new URL(endpoint, window.location.origin);
        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                url.searchParams.append(key, value);
            });
        }

        // Get CSRF token from meta tag
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // Prepare headers
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        // Make request
        const response = await fetch(url, {
            method,
            headers,
            body: body ? JSON.stringify(body) : null,
            credentials: 'same-origin'
        });

        // Handle non-200 responses
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        // Handle API-level errors
        if (data.error) {
            throw new Error(data.error);
        }

        return data;
    } catch (error) {
        handleApiError(error, `${method} ${endpoint}`);
    } finally {
        if (shouldShowLoader) {
            loadingState.hide();
        }
    }
}

// Initialize loading state handler
loadingState.init();

/**
 * Display a custom alert on the page.
 * @param {string} message - Alert message.
 * @param {string} type - Alert type ('success' or 'error').
 */
function showAlert(message, type = 'success') {
    const alertBox = document.createElement("div");
    alertBox.className = `alert alert-${type === "error" ? "danger" : "success"} alert-dismissible fade show`;
    alertBox.role = "alert";
    alertBox.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.prepend(alertBox);

    setTimeout(() => alertBox.remove(), 5000); // Auto-remove after 5 seconds
}

/**
 * Show a Bootstrap modal by ID.
 * @param {string} modalId - The ID of the modal to show.
 */
function showModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        console.error(`Modal with ID ${modalId} not found.`);
        return;
    }
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

/**
 * Hide a Bootstrap modal by ID.
 * @param {string} modalId - The ID of the modal to hide.
 */
function hideModal(modalId) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        console.error(`Modal with ID ${modalId} not found.`);
        return;
    }
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
        modal.hide();
    }
}

/**
 * Populate a Bootstrap modal with data.
 * @param {string} modalId - The ID of the modal to populate.
 * @param {object} data - The data to populate the modal with.
 */
function populateModal(modalId, data) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        console.error(`Modal with ID ${modalId} not found.`);
        return;
    }
    Object.keys(data).forEach(key => {
        const element = modalElement.querySelector(`[data-key="${key}"]`);
        if (element) {
            element.textContent = data[key];
        }
    });
}

/**
 * Validate email format.
 * @param {string} email - The email to validate.
 * @returns {boolean} - True if the email format is valid, otherwise false.
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate that the input is not empty.
 * @param {string} value - The value to validate.
 * @returns {boolean} - True if the value is not empty, otherwise false.
 */
function validateNotEmpty(value) {
    return value.trim().length > 0;
}

/**
 * Validate a date range.
 * @param {string} startDate - The start date.
 * @param {string} endDate - The end date.
 * @returns {boolean} - True if the start date is before or equal to the end date, otherwise false.
 */
function validateDateRange(startDate, endDate) {
    return new Date(startDate) <= new Date(endDate);
}

/**
 * Format a Date object into a string using a given format.
 * @param {Date} date - The date to format.
 * @param {string} format - The format string (e.g., "YYYY-MM-DD").
 * @returns {string} - The formatted date string.
 */
function formatDate(date, format = 'YYYY-MM-DD') {
    const pad = (n) => (n < 10 ? '0' + n : n);
    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());

    return format.replace('YYYY', year).replace('MM', month).replace('DD', day);
}

/**
 * Return today's date as a formatted string.
 * @returns {string} - Today's date formatted as "YYYY-MM-DD".
 */
function getToday() {
    return formatDate(new Date());
}

/**
 * Format a number as currency.
 * @param {number} amount - The amount to format.
 * @param {string} currency - The currency code (e.g., 'PLN').
 * @returns {string} - The formatted currency string.
 */
function formatCurrency(amount, currency = 'PLN') {
    return new Intl.NumberFormat('pl-PL', { style: 'currency', currency }).format(amount);
}

export { fetchData, showAlert, showModal, hideModal, populateModal, validateEmail, validateNotEmpty, validateDateRange, formatDate, getToday, formatCurrency, handleApiError };
