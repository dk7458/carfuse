import ajax from './ajax';
import { showErrorToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    loadDashboardContent();
    setInterval(checkUserActivity, 60000); // Aktualizuj statystyki tylko przy aktywności użytkownika
    window.addEventListener('resize', handleResponsiveUpdates);
});

/**
 * Loads the main dashboard content dynamically.
 */
async function loadDashboardContent() {
    try {
        const response = await ajax.get('/user/overview.php');
        document.getElementById('dashboard-content').innerHTML = response;
        fetchDashboardData();
    } catch (error) {
        showErrorToast('Nie udało się załadować zawartości pulpitu.');
        console.error('Błąd ładowania zawartości pulpitu:', error);
    }
}

/**
 * Fetches dashboard data based on user role.
 */
async function fetchDashboardData() {
    const role = getUserRole();
    if (role === 'admin') {
        await fetchAdminStatistics();
    } else {
        await fetchUserStatistics();
    }
}

/**
 * Fetches admin-specific statistics for the dashboard.
 */
async function fetchAdminStatistics() {
    try {
        showLoadingIndicators();
        const [totalUsers, totalBookings, totalRevenue] = await Promise.all([
            secureFetch('/statistics/totalUsers'),
            secureFetch('/statistics/totalBookings'),
            secureFetch('/statistics/totalRevenue')
        ]);
        updateWidgets({
            totalUsers: totalUsers.data,
            totalBookings: totalBookings.data,
            totalRevenue: totalRevenue.data
        });
    } catch (error) {
        showErrorToast('Nie udało się pobrać statystyk administratora.');
        console.error('Błąd pobierania statystyk administratora:', error);
    } finally {
        hideLoadingIndicators();
    }
}

/**
 * Fetches user-specific statistics for the dashboard.
 */
async function fetchUserStatistics() {
    try {
        showLoadingIndicators();
        const response = await secureFetch('/statistics/user');
        updateWidgets(response.data);
    } catch (error) {
        showErrorToast('Nie udało się pobrać statystyk użytkownika.');
        console.error('Błąd pobierania statystyk użytkownika:', error);
    } finally {
        hideLoadingIndicators();
    }
}

/**
 * Updates dashboard widgets based on API data.
 */
function updateWidgets(data) {
    updateWidget('total-users', data.totalUsers);
    updateWidget('active-sessions', data.activeSessions);
    updateWidget('new-bookings', data.newBookings);
    updateWidget('total-revenue', formatCurrency(data.totalRevenue));
}

/**
 * Updates a single widget if it exists.
 */
function updateWidget(widgetId, value) {
    const widget = document.getElementById(widgetId);
    if (widget) {
        widget.innerText = value ?? 'Brak danych';
    }
}

/**
 * Handles dynamic UI responsiveness updates.
 */
function handleResponsiveUpdates() {
    console.log('Zaktualizowano UI na podstawie zmiany rozmiaru ekranu.');
    // Można dodać dodatkowe funkcje do aktualizacji widżetów lub układu
}

/**
 * Formats numbers into readable currency format.
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(amount);
}

/**
 * Checks user activity and updates statistics only if the tab is active.
 */
function checkUserActivity() {
    if (document.visibilityState === 'visible') {
        fetchDashboardData();
    }
}

/**
 * Shows loading indicators for widgets.
 */
function showLoadingIndicators() {
    const widgets = document.querySelectorAll('.widget');
    widgets.forEach(widget => {
        widget.classList.add('loading');
    });
}

/**
 * Hides loading indicators for widgets.
 */
function hideLoadingIndicators() {
    const widgets = document.querySelectorAll('.widget');
    widgets.forEach(widget => {
        widget.classList.remove('loading');
    });
}

/**
 * Mock function to get user role.
 */
function getUserRole() {
    // Replace with actual implementation
    return 'user'; // or 'admin'
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    return parts.length === 2 ? parts.pop().split(';').shift() : null;
}

function deleteCookie(name) {
    document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Secure; SameSite=Strict';
}

function parseJwt(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(c =>
            '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
        ).join(''));
        return JSON.parse(jsonPayload);
    } catch (e) {
        return null;
    }
}

function checkJwtExpiration() {
    const token = getCookie('jwt');
    if (token) {
        const payload = parseJwt(token);
        if (payload && payload.exp) {
            if (Date.now() >= payload.exp * 1000) { // token expired
                deleteCookie('jwt');
                deleteCookie('refresh_token');
                window.location.href = '/auth/login'; // redirect to login page
            }
        }
    }
}

/**
 * Checks if the JWT token is expired.
 * @param {string} token 
 * @return {boolean}
 */
function isTokenExpired(token) {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return payload.exp < Date.now() / 1000;
}

/**
 * Clears auth cookies and redirects to login.
 */
function autoLogout() {
    deleteCookie('jwt');
    deleteCookie('refresh_token');
    window.location.href = '/auth/login.php';
}

/**
 * Checks JWT on page load.
 */
(function checkJwtOnLoad() {
    const token = getCookie('jwt');
    if (token && isTokenExpired(token)) {
        autoLogout();
    }
})();

/**
 * A wrapper for fetch that adds X-Auth-Token header.
 */
async function secureFetch(url, options = {}) {
    // Always send JWT if available
    const token = getCookie('jwt');
    if (token) {
        if (!options.headers) options.headers = {};
        options.headers["X-Auth-Token"] = "Bearer " + token;
    }
    return fetch(url, options).then(response => {
        // Optionally implement silent re-authentication here using refresh tokens
        return response;
    });
}

/**
 * Checks token existence and expiration on protected pages.
 */
function checkTokenAndRedirect() {
    const token = getCookie('jwt');
    if (!token || isTokenExpired(token)) {
        deleteCookie('jwt');
        deleteCookie('refresh_token');
        window.location.href = '/auth/login.php';
    }
}

// Run the check on page load
checkTokenAndRedirect();
// Periodically check every 30 seconds
setInterval(checkTokenAndRedirect, 30000);

// Run the expiration check on page load
checkJwtExpiration();
