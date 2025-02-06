document.addEventListener('DOMContentLoaded', function () {
    fetchStatistics();
    setInterval(checkUserActivity, 60000); // Aktualizuj statystyki tylko przy aktywności użytkownika

    window.addEventListener('resize', handleResponsiveUpdates);
});

/**
 * Pobiera w czasie rzeczywistym statystyki dashboardu.
 */
async function fetchStatistics() {
    try {
        const response = await fetch('/api/statistics');
        if (!response.ok) throw new Error('Błąd odpowiedzi API');

        const data = await response.json();
        updateWidgets(data);
    } catch (error) {
        showErrorToast('Nie udało się pobrać statystyk.');
        console.error('Błąd pobierania statystyk:', error);
    }
}

/**
 * Aktualizuje widżety dashboardu na podstawie danych API.
 */
function updateWidgets(data) {
    updateWidget('total-users', data.totalUsers);
    updateWidget('active-sessions', data.activeSessions);
    updateWidget('new-bookings', data.newBookings);
    updateWidget('total-revenue', formatCurrency(data.totalRevenue));
}

/**
 * Aktualizuje pojedynczy widget, jeśli istnieje.
 */
function updateWidget(widgetId, value) {
    const widget = document.getElementById(widgetId);
    if (widget) {
        widget.innerText = value ?? 'Brak danych';
    }
}

/**
 * Obsługuje dynamiczną responsywność UI.
 */
function handleResponsiveUpdates() {
    console.log('Aktualizacja UI na podstawie zmiany rozmiaru ekranu.');
    // Tutaj można dodać konkretne akcje dla responsywności dashboardu
}

/**
 * Formatuje kwoty na czytelny format walutowy.
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(amount);
}

/**
 * Sprawdza aktywność użytkownika i aktualizuje statystyki tylko przy aktywności.
 */
function checkUserActivity() {
    if (document.visibilityState === 'visible') {
        fetchStatistics();
    }
}

/**
 * Wyświetla komunikat błędu w UI.
 */
function showErrorToast(message) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = 'toast toast-error';
    toast.innerText = message;

    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
