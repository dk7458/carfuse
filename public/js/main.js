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

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const errorContainer = document.getElementById('error-container');

    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);

    /**
     * Obsługuje logowanie użytkownika.
     */
    async function handleLogin(event) {
        event.preventDefault();
        clearErrors();

        const formData = new FormData(loginForm);
        const username = formData.get('username').trim();
        const password = formData.get('password').trim();

        if (!validateCredentials(username, password)) return;

        try {
            const response = await ajax.post('/login', { username, password });
            if (response.success) {
                ajax.setToken(response.token);
                redirectToDashboard();
            } else {
                showError(response.error || 'Błąd podczas logowania.');
            }
        } catch (error) {
            console.error('Błąd logowania:', error);
            showError('Wystąpił problem podczas logowania. Spróbuj ponownie.');
        }
    }

    /**
     * Obsługuje rejestrację użytkownika.
     */
    async function handleRegister(event) {
        event.preventDefault();
        clearErrors();

        const formData = new FormData(registerForm);
        const username = formData.get('username').trim();
        const password = formData.get('password').trim();
        const confirmPassword = formData.get('confirm_password').trim();

        if (!validateCredentials(username, password, confirmPassword)) return;

        try {
            const response = await ajax.post('/register', { username, password });
            if (response.success) {
                ajax.setToken(response.token);
                redirectToDashboard();
            } else {
                showError(response.error || 'Błąd podczas rejestracji.');
            }
        } catch (error) {
            console.error('Błąd rejestracji:', error);
            showError('Wystąpił problem podczas rejestracji. Spróbuj ponownie.');
        }
    }

    /**
     * Przekierowuje użytkownika do dashboardu.
     */
    function redirectToDashboard() {
        window.location.href = '/dashboard';
    }

    /**
     * Wyświetla komunikat błędu.
     */
    function showError(message) {
        if (errorContainer) {
            errorContainer.innerText = message;
            errorContainer.style.display = 'block';
        }
    }

    /**
     * Czyści komunikaty błędów.
     */
    function clearErrors() {
        if (errorContainer) {
            errorContainer.innerText = '';
            errorContainer.style.display = 'none';
        }
    }

    /**
     * Sprawdza poprawność danych logowania i rejestracji.
     */
    function validateCredentials(username, password, confirmPassword = null) {
        if (!username || username.length < 3) {
            showError('Nazwa użytkownika musi mieć co najmniej 3 znaki.');
            return false;
        }

        if (!password || password.length < 6) {
            showError('Hasło musi mieć co najmniej 6 znaków.');
            return false;
        }

        if (confirmPassword !== null && password !== confirmPassword) {
            showError('Hasła nie są identyczne.');
            return false;
        }

        return true;
    }

    /**
     * Wylogowuje użytkownika i czyści tokeny autoryzacji.
     */
    function logout() {
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
    }

    /**
     * Sprawdza, czy token sesji wygasł i wylogowuje użytkownika w razie potrzeby.
     */
    function refreshSession() {
        const token = localStorage.getItem('auth_token');
        if (!token) return;

        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            const expiration = payload.exp * 1000;
            const now = Date.now();

            if (now >= expiration) {
                logout();
            }
        } catch (error) {
            console.error('Błąd walidacji tokena:', error);
            logout();
        }
    }

    // Sprawdzanie sesji co minutę
    setInterval(refreshSession, 60000);
});

document.addEventListener('DOMContentLoaded', function () {
    const pickupDateInput = document.getElementById('pickup-date');
    const bookingForm = document.getElementById('booking-form');
    const errorContainer = document.getElementById('error-container');
    const loadingIndicator = document.getElementById('loading-indicator');

    if (pickupDateInput) pickupDateInput.addEventListener('change', fetchAvailableVehicles);
    if (bookingForm) bookingForm.addEventListener('submit', submitBookingRequest);

    /**
     * Pobiera dostępne pojazdy po wybraniu daty odbioru.
     */
    async function fetchAvailableVehicles() {
        const pickupDate = pickupDateInput.value.trim();
        if (!pickupDate) return;

        showLoadingIndicator();

        try {
            const response = await fetch(`/vehicles/available?pickup_date=${pickupDate}`);
            const data = await response.json();
            hideLoadingIndicator();

            if (data.vehicles && data.vehicles.length > 0) {
                displayAvailableVehicles(data.vehicles);
            } else {
                showError('Brak dostępnych pojazdów na wybrany termin.');
            }
        } catch (error) {
            hideLoadingIndicator();
            console.error('Błąd pobierania dostępnych pojazdów:', error);
            showError('Nie udało się pobrać dostępnych pojazdów.');
        }
    }

    /**
     * Wyświetla dostępne pojazdy w interfejsie użytkownika.
     */
    function displayAvailableVehicles(vehicles) {
        const vehiclesContainer = document.getElementById('vehicles-container');
        vehiclesContainer.innerHTML = '';

        vehicles.forEach(vehicle => {
            const vehicleElement = document.createElement('div');
            vehicleElement.className = 'vehicle';
            vehicleElement.innerHTML = `
                <p>${vehicle.name}</p>
                <p>${vehicle.type}</p>
            `;
            vehiclesContainer.appendChild(vehicleElement);
        });
    }

    /**
     * Sprawdza poprawność lokalizacji odbioru i zwrotu.
     */
    function validateLocations() {
        const pickupLocation = document.getElementById('pickup-location').value.trim();
        const dropoffLocation = document.getElementById('dropoff-location').value.trim();

        if (!pickupLocation || !dropoffLocation) {
            showError('Miejsce odbioru i zwrotu są wymagane.');
            return false;
        }

        return true;
    }

    /**
     * Waliduje formularz przed wysłaniem.
     */
    function validateBookingForm() {
        let isValid = true;
        const requiredFields = ['pickup-date', 'return-date', 'pickup-location', 'dropoff-location'];

        requiredFields.forEach(field => {
            const input = document.getElementById(field);
            if (!input || !input.value.trim()) {
                showError(`Pole ${field.replace('-', ' ')} jest wymagane.`);
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Obsługuje przesyłanie formularza rezerwacji.
     */
    async function submitBookingRequest(event) {
        event.preventDefault();
        clearErrors();

        if (!validateLocations() || !validateBookingForm()) return;

        const formData = new FormData(bookingForm);

        showLoadingIndicator();

        try {
            const response = await fetch('/booking/create', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            hideLoadingIndicator();

            if (data.success) {
                alert('Rezerwacja zakończona sukcesem!');
                window.location.href = "/bookings/view";
            } else {
                showError(data.error || 'Wystąpił problem podczas tworzenia rezerwacji.');
            }
        } catch (error) {
            hideLoadingIndicator();
            console.error('Błąd tworzenia rezerwacji:', error);
            showError('Nie udało się utworzyć rezerwacji.');
        }
    }

    /**
     * Pokazuje wskaźnik ładowania.
     */
    function showLoadingIndicator() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'block';
        }
    }

    /**
     * Ukrywa wskaźnik ładowania.
     */
    function hideLoadingIndicator() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }

    /**
     * Wyświetla komunikat o błędzie.
     */
    function showError(message) {
        if (errorContainer) {
            errorContainer.innerText = message;
            errorContainer.style.display = 'block';
        }
    }

    /**
     * Czyści komunikaty o błędach.
     */
    function clearErrors() {
        if (errorContainer) {
            errorContainer.innerText = '';
            errorContainer.style.display = 'none';
        }
    }
});
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
document.addEventListener('DOMContentLoaded', function () {
    initDocumentForm();
});

/**
 * Inicjalizuje obsługę formularzy dokumentów.
 */
function initDocumentForm() {
    const uploadForm = document.getElementById('document-upload-form');
    const signButton = document.getElementById('sign-button');
    const uploadInput = document.getElementById('uploadButton');

    if (uploadForm) uploadForm.addEventListener('submit', uploadDocument);
    if (signButton) signButton.addEventListener('click', handleSignButtonClick);
    if (uploadInput) uploadInput.addEventListener('change', handleFileSelection);
}

/**
 * Obsługuje przesyłanie dokumentów.
 */
async function uploadDocument(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    try {
        const response = await fetch('/api/documents/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': 'Bearer ' + getAuthToken()
            }
        });
        const data = await response.json();
        
        if (data.success) {
            alert('Dokument przesłany pomyślnie.');
            previewDocument(data.documentUrl);
        } else {
            showError('Błąd przesyłania dokumentu: ' + data.message);
        }
    } catch (error) {
        console.error('Błąd przesyłania dokumentu:', error);
        showError('Wystąpił problem podczas przesyłania dokumentu.');
    }
}

/**
 * Obsługuje wybór pliku i jego podgląd.
 */
function handleFileSelection(event) {
    const file = event.target.files[0];
    if (!file) return;

    previewContract(file);
}

/**
 * Wyświetla podgląd dokumentu przed podpisaniem.
 */
function previewDocument(documentUrl) {
    const previewFrame = document.getElementById('document-preview');
    if (previewFrame) {
        previewFrame.src = documentUrl;
        previewFrame.style.display = 'block';
    }
}

/**
 * Obsługuje kliknięcie przycisku podpisywania.
 */
function handleSignButtonClick() {
    const documentId = document.getElementById('document-id').value.trim();
    if (!documentId) {
        showError('Brak wybranego dokumentu do podpisania.');
        return;
    }

    signDocument(documentId);
}

/**
 * Wysyła żądanie podpisania dokumentu do API.
 */
async function signDocument(documentId) {
    try {
        const response = await fetch('/api/documents/sign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getAuthToken()
            },
            body: JSON.stringify({ documentId })
        });
        const data = await response.json();
        
        if (data.success) {
            alert('Dokument został pomyślnie podpisany.');
        } else {
            showError('Błąd podpisywania dokumentu: ' + data.message);
        }
    } catch (error) {
        console.error('Błąd podpisywania dokumentu:', error);
        showError('Wystąpił problem podczas podpisywania dokumentu.');
    }
}

/**
 * Wyświetla podgląd dokumentu przed podpisaniem.
 */
function previewContract(file) {
    const reader = new FileReader();
    reader.onload = function (event) {
        const previewFrame = document.getElementById('contractPreview');
        if (previewFrame) {
            previewFrame.src = event.target.result;
        }
    };
    reader.readAsDataURL(file);
}

/**
 * Wyświetla komunikat o błędzie.
 */
function showError(message) {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
        errorContainer.innerText = message;
        errorContainer.style.display = 'block';
    }
}

/**
 * Pobiera token autoryzacyjny użytkownika.
 */
function getAuthToken() {
    return localStorage.getItem('auth_token') || '';
}
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!validateForm(form)) {
                event.preventDefault();
            }
        });

        attachRealTimeValidation(form);
    });
});

/**
 * Waliduje formularz przed wysłaniem.
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        if (!validateInput(input)) {
            isValid = false;
        }
    });

    return isValid;
}

/**
 * Dołącza walidację w czasie rzeczywistym dla pól formularza.
 */
function attachRealTimeValidation(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        input.addEventListener('input', function () {
            validateInput(input);
        });
    });
}

/**
 * Waliduje pojedyncze pole formularza.
 */
function validateInput(input) {
    const value = input.value.trim();
    const type = input.type;

    if (!value) {
        showError(input, 'To pole jest wymagane.');
        return false;
    }

    if (type === 'email' && !isValidEmail(value)) {
        showError(input, 'Wprowadź poprawny adres e-mail.');
        return false;
    }

    if (type === 'password' && value.length < 6) {
        showError(input, 'Hasło musi zawierać co najmniej 6 znaków.');
        return false;
    }

    if (input.dataset.minLength && value.length < input.dataset.minLength) {
        showError(input, `To pole musi mieć co najmniej ${input.dataset.minLength} znaków.`);
        return false;
    }

    clearError(input);
    return true;
}

/**
 * Wyświetla komunikat o błędzie obok pola formularza.
 */
function showError(input, message) {
    let error = input.nextElementSibling;
    if (!error || !error.classList.contains('error-message')) {
        error = document.createElement('div');
        error.classList.add('error-message');
        input.parentNode.insertBefore(error, input.nextSibling);
    }
    error.textContent = message;
    input.classList.add('error');
}

/**
 * Usuwa komunikat o błędzie.
 */
function clearError(input) {
    let error = input.nextElementSibling;
    if (error && error.classList.contains('error-message')) {
        error.remove();
    }
    input.classList.remove('error');
}

/**
 * Sprawdza poprawność adresu e-mail.
 */
function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}

document.addEventListener('DOMContentLoaded', function () {
    fetchNotifications();
});

/**
 * Pobiera powiadomienia z serwera.
 */
async function fetchNotifications() {
    try {
        const notifications = await ajax.get('/notifications');
        if (notifications.length > 0) {
            displayNotifications(notifications);
        } else {
            displayNoNotificationsMessage();
        }
    } catch (error) {
        console.error('Błąd pobierania powiadomień:', error);
    }
}

/**
 * Wyświetla powiadomienia w interfejsie użytkownika.
 */
function displayNotifications(notifications) {
    const notificationsContainer = document.getElementById('notifications-container');
    if (!notificationsContainer) return;

    notificationsContainer.innerHTML = '';

    notifications.forEach(notification => {
        const notificationElement = document.createElement('div');
        notificationElement.className = `notification ${notification.read ? 'read' : 'unread'}`;
        notificationElement.innerHTML = `
            <p>${notification.message}</p>
            <button class="mark-as-read" data-id="${notification.id}">Oznacz jako przeczytane</button>
        `;
        notificationsContainer.appendChild(notificationElement);
    });

    attachMarkAsReadListeners();
}

/**
 * Dodaje obsługę kliknięcia przycisku "Oznacz jako przeczytane".
 */
function attachMarkAsReadListeners() {
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function () {
            markAsRead(this.dataset.id);
        });
    });
}

/**
 * Oznacza powiadomienie jako przeczytane.
 */
async function markAsRead(notificationId) {
    try {
        const response = await fetch(`/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getAuthToken()
            }
        });

        const data = await response.json();

        if (data.success) {
            updateNotificationStatus(notificationId);
        } else {
            console.error('Błąd oznaczania powiadomienia jako przeczytanego:', data.error);
        }
    } catch (error) {
        console.error('Błąd oznaczania powiadomienia jako przeczytanego:', error);
    }
}

/**
 * Aktualizuje status powiadomienia bez ponownego ładowania wszystkich powiadomień.
 */
function updateNotificationStatus(notificationId) {
    const notificationElement = document.querySelector(`.mark-as-read[data-id="${notificationId}"]`);
    if (notificationElement) {
        notificationElement.closest('.notification').classList.add('read');
        notificationElement.remove();
    }
}

/**
 * Wyświetla informację, gdy brak powiadomień.
 */
function displayNoNotificationsMessage() {
    const notificationsContainer = document.getElementById('notifications-container');
    if (!notificationsContainer) return;

    notificationsContainer.innerHTML = `<p class="text-muted">Brak nowych powiadomień.</p>`;
}

/**
 * Pobiera token autoryzacyjny użytkownika.
 */
function getAuthToken() {
    return localStorage.getItem('auth_token') || '';
}

document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');

    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault();
        if (validateForm()) {
            const paymentDetails = new FormData(paymentForm);
            processPayment(paymentDetails);
        }
    });

    function validateForm() {
        let isValid = true;
        const cardNumber = document.getElementById('cardNumber').value;
        const expiryDate = document.getElementById('expiryDate').value;
        const cvv = document.getElementById('cvv').value;
        const errorMessage = document.getElementById('errorMessage');

        errorMessage.innerHTML = '';

        if (!cardNumber.match(/^\d{16}$/)) {
            errorMessage.innerHTML += '<p>Invalid card number. Must be 16 digits.</p>';
            isValid = false;
        }
        if (!expiryDate.match(/^\d{2}\/\d{2}$/)) {
            errorMessage.innerHTML += '<p>Invalid expiry date. Must be in MM/YY format.</p>';
            isValid = false;
        }
        if (!cvv.match(/^\d{3}$/)) {
            errorMessage.innerHTML += '<p>Invalid CVV. Must be 3 digits.</p>';
            isValid = false;
        }

        return isValid;
    }

    async function processPayment(paymentDetails) {
        try {
            const response = await ajax.post('/payments', paymentDetails);
            if (response.success) {
                window.location.href = '/booking/confirmation';
            } else {
                displayErrors(response.errors);
            }
        } catch (error) {
            displayErrors(['An error occurred while processing the payment. Please try again.']);
        }
    }

    function displayErrors(errors) {
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.innerHTML = '';
        errors.forEach(function(error) {
            errorMessage.innerHTML += `<p>${error}</p>`;
        });
    }
});
// Function to handle profile updates
async function updateProfile(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    if (!validateProfileForm(formData)) {
        return;
    }

    try {
        const response = await fetch('/api/profile/update', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            alert('Profile updated successfully');
        } else {
            alert('Error updating profile: ' + data.message);
        }
    } catch (error) {
        console.error('Error updating profile:', error);
    }
}

// Function to allow avatar image uploads
function handleAvatarUpload(event) {
    const fileInput = event.target;
    const file = fileInput.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Function to validate form inputs before saving
function validateProfileForm(formData) {
    const name = formData.get('name');
    const email = formData.get('email');
    const password = formData.get('password');

    if (!name || !email || (password && password.length < 6)) {
        alert('Please fill out all required fields and ensure password is at least 6 characters long.');
        return false;
    }
    return true;
}

// Initialize profile form
function initProfileForm() {
    document.getElementById('profile-form').addEventListener('submit', updateProfile);
    document.getElementById('avatar-upload').addEventListener('change', handleAvatarUpload);
}

// Call initProfileForm when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', initProfileForm);
document.getElementById('registerForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (response.ok) {
            showToast('success', 'Registration successful! Welcome to Carfuse.');
            this.reset();
        } else {
            showToast('error', result.message || 'Registration failed.');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'An unexpected error occurred.');
    }
});

function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 show`;
    toast.role = 'alert';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
// Function to create a toast
function createToast(type, message, autoDismiss = true, dismissTime = 3000) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        console.error('Toast container not found');
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerText = message;

    toastContainer.appendChild(toast);

    if (autoDismiss) {
        setTimeout(() => {
            toast.remove();
        }, dismissTime);
    }
}

// Function to show success toast
function showSuccessToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('success', message, autoDismiss, dismissTime);
}

// Function to show warning toast
function showWarningToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('warning', message, autoDismiss, dismissTime);
}

// Function to show error toast
function showErrorToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('error', message, autoDismiss, dismissTime);
}

// Ensure toast container exists
document.addEventListener('DOMContentLoaded', () => {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
    }
});
