class Ajax {
    constructor() {
        this.baseUrl = '/api';
        this.token = localStorage.getItem('auth_token') || null;
    }

    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    async request(endpoint, method = 'GET', data = null) {
        const url = `${this.baseUrl}${endpoint}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': this.token ? `Bearer ${this.token}` : ''
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Błąd API:', error);
            throw error;
        }
    }

    get(endpoint) {
        return this.request(endpoint, 'GET');
    }

    post(endpoint, data) {
        return this.request(endpoint, 'POST', data);
    }

    put(endpoint, data) {
        return this.request(endpoint, 'PUT', data);
    }

    delete(endpoint) {
        return this.request(endpoint, 'DELETE');
    }
}

const ajax = new Ajax();
export default ajax;
