(function() {
    function apiFetch(url, options = {}) {
        if (!options.headers) {
            options.headers = {};
        }
        // Attach bearer token
        const token = localStorage.getItem('token');
        if (token) {
            options.headers['Authorization'] = 'Bearer ' + token;
        }

        return fetch(url, options)
            .then(async response => {
                if (response.status === 401) {
                    console.warn('Unauthorized response. Attempting token refresh...');
                    // ...token refresh logic...
                    // After refresh, retry original request or handle failure
                }
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Request failed [${response.status}]: ${errorText}`);
                }
                return response;
            })
            .catch(error => {
                console.error('API fetch error:', error.message);
                throw error;
            });
    }

    function getUserData(userId) {
        return apiFetch(`/api/user/${userId}`);
    }

    function updateProfile(data) {
        return apiFetch('/api/profile/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
    }

    // Expose functions to the global scope
    window.apiFetch = apiFetch;
    window.getUserData = getUserData;
    window.updateProfile = updateProfile;

    // Log successful API script loading
    console.log('API script loaded successfully');
})();
