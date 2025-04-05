class CookieHandler {
    constructor(options = {}) {
        this.options = {
            secure: true, // Default to secure cookies
            httpOnly: true, // Default to HttpOnly cookies
            path: '/',
            domain: null,
            expires: 365, // Default expiration of 365 days
            ...options
        };
        this.consentCookieName = 'cookie_consent';
        this.userPreferencesCookieName = 'user_preferences';
        this.translations = {
            'en': {
                'consentAccepted': 'Consent accepted.',
                'consentRevoked': 'Consent revoked. Some site features may be disabled.'
            },
            'pl': {
                'consentAccepted': 'Zgoda zaakceptowana.',
                'consentRevoked': 'Zgoda cofnięta. Niektóre funkcje strony mogą być wyłączone.'
            }
        };
        this.currentLanguage = 'en'; // Default language
    }

    setLanguage(lang) {
        this.currentLanguage = lang;
    }

    getTranslation(key) {
        return this.translations[this.currentLanguage][key] || this.translations['en'][key] || key;
    }

    setCookie(name, value, options = {}) {
        let cookieString = `${name}=${value}; path=${this.options.path}`;

        if (options.expires || this.options.expires) {
            const days = options.expires || this.options.expires;
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            cookieString += `; expires=${date.toUTCString()}`;
        }

        if (this.options.domain) {
            cookieString += `; domain=${this.options.domain}`;
        }

        if (this.options.secure) {
            cookieString += '; secure';
        }

        if (this.options.httpOnly) {
            cookieString += '; HttpOnly';
        }

        document.cookie = cookieString;
    }

    getCookie(name) {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i].trim();
            // Does this cookie string begin with the name we want?
            if (cookie.startsWith(name + '=')) {
                return cookie.substring(name.length + 1);
            }
        }
        return null;
    }

    deleteCookie(name) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=${this.options.path}`;
    }

    hasConsent() {
        return this.getCookie(this.consentCookieName) === 'true';
    }

    acceptConsent() {
        return fetch("/api/consent/accept.php", { method: "POST" })
            .then(() => {
                this.setCookie(this.consentCookieName, 'true', { expires: 365 });
                alert(this.getTranslation('consentAccepted'));
            });
    }

    revokeConsent() {
        return fetch("/api/consent/revoke.php", { method: "POST" })
            .then(() => {
                this.deleteCookie(this.consentCookieName);
                alert(this.getTranslation('consentRevoked'));
            });
    }

    setUserPreferences(preferences) {
        this.setCookie(this.userPreferencesCookieName, JSON.stringify(preferences), { expires: 365 });
    }

    getUserPreferences() {
        const prefs = this.getCookie(this.userPreferencesCookieName);
        return prefs ? JSON.parse(prefs) : {};
    }
}

// Export the CookieHandler class so it can be imported in other files
export default CookieHandler;
