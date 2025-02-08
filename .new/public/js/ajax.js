class Ajax {
    constructor() {
        this.baseUrl = '/api';
        this.token = localStorage.getItem('auth_token') || null;
    }

    /**
     * Stores the authentication token for future requests.
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    /**
     * Makes an API request with automatic retry and session handling.
     */
    async request(endpoint, method = 'GET', data = null, retry = true) {
        const url = `${this.baseUrl}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            'Authorization': this.token ? `Bearer ${this.token}` : ''
        };

        const options = { method, headers };

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);

            if (response.status === 401 && retry) {
                // Attempt token refresh before retrying request
                const refreshed = await this.refreshToken();
                if (refreshed) {
                    return this.request(endpoint, method, data, false);
                }
                throw new Error('Unauthorized: Session expired.');
            }

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Błąd API:', error);
            throw error;
        }
    }

    /**
     * Fetches data using GET method.
     */
    get(endpoint) {
        return this.request(endpoint, 'GET');
    }

    /**
     * Sends data using POST method.
     */
    post(endpoint, data) {
        return this.request(endpoint, 'POST', data);
    }

    /**
     * Updates data using PUT method.
     */
    put(endpoint, data) {
        return this.request(endpoint, 'PUT', data);
    }

    /**
     * Deletes a resource using DELETE method.
     */
    delete(endpoint) {
        return this.request(endpoint, 'DELETE');
    }

    /**
     * Attempts to refresh the session token.
     */
    async refreshToken() {
        try {
            const response = await fetch(`${this.baseUrl}/session/refresh`, {
                method: 'POST',
                headers: { 'Authorization': this.token ? `Bearer ${this.token}` : '' }
            });

            if (!response.ok) {
                this.clearToken();
                return false;
            }

            const data = await response.json();
            if (data.success && data.token) {
                this.setToken(data.token);
                return true;
            }

            return false;
        } catch (error) {
            console.error('Błąd odświeżania tokena:', error);
            this.clearToken();
            return false;
        }
    }

    /**
     * Clears stored authentication token.
     */
    clearToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }
}

// Ensures global availability
window.ajax = new Ajax();
