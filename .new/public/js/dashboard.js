import ajax from './ajax';
import { showErrorToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    fetchStatistics();
    setInterval(checkUserActivity, 60000); // Aktualizuj statystyki tylko przy aktywności użytkownika
    window.addEventListener('resize', handleResponsiveUpdates);
});

/**
 * Fetches real-time statistics for the dashboard.
 */
async function fetchStatistics() {
    try {
        const response = await ajax.get('/statistics');
        updateWidgets(response);
    } catch (error) {
        showErrorToast('Nie udało się pobrać statystyk.');
        console.error('Błąd pobierania statystyk:', error);
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
        fetchStatistics();
    }
}
