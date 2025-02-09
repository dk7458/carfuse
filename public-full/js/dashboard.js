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
            ajax.get('/statistics/totalUsers'),
            ajax.get('/statistics/totalBookings'),
            ajax.get('/statistics/totalRevenue')
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
        const response = await ajax.get('/statistics/user');
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
