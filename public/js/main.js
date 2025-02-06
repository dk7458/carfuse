/**************************************
 * Global Utility Functions & Classes *
 **************************************/

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
        console.error('API Error:', error);
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
  
  function getAuthToken() {
    return localStorage.getItem('auth_token') || '';
  }
  
  function displayGlobalError(message) {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
      errorContainer.innerText = message;
      errorContainer.style.display = 'block';
    }
  }
  
  function clearGlobalError() {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
      errorContainer.innerText = '';
      errorContainer.style.display = 'none';
    }
  }
  
  /* Toast Functions */
  function ensureToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
  }
  
  function createToast(type, message, autoDismiss = true, dismissTime = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) {
      console.error('Toast container not found');
      return;
    }
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerText = message;
    container.appendChild(toast);
    if (autoDismiss) {
      setTimeout(() => toast.remove(), dismissTime);
    }
  }
  
  function showSuccessToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('success', message, autoDismiss, dismissTime);
  }
  
  function showWarningToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('warning', message, autoDismiss, dismissTime);
  }
  
  function showErrorToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('error', message, autoDismiss, dismissTime);
  }
  
  /* Field-level Validation (for individual inputs) */
  function showFieldError(input, message) {
    let error = input.nextElementSibling;
    if (!error || !error.classList.contains('error-message')) {
      error = document.createElement('div');
      error.classList.add('error-message');
      input.parentNode.insertBefore(error, input.nextSibling);
    }
    error.textContent = message;
    input.classList.add('error');
  }
  
  function clearFieldError(input) {
    let error = input.nextElementSibling;
    if (error && error.classList.contains('error-message')) {
      error.remove();
    }
    input.classList.remove('error');
  }
  
  /* Generic Form Validation */
  function genericValidateInput(input) {
    const value = input.value.trim();
    const type = input.type;
    if (!value) {
      showFieldError(input, 'This field is required.');
      return false;
    }
    if (type === 'email' && !isValidEmail(value)) {
      showFieldError(input, 'Please enter a valid email address.');
      return false;
    }
    if (type === 'password' && value.length < 6) {
      showFieldError(input, 'Password must be at least 6 characters.');
      return false;
    }
    if (input.dataset.minLength && value.length < input.dataset.minLength) {
      showFieldError(input, `This field must be at least ${input.dataset.minLength} characters.`);
      return false;
    }
    clearFieldError(input);
    return true;
  }
  
  function attachGenericValidation(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    inputs.forEach(input => {
      input.addEventListener('input', () => {
        genericValidateInput(input);
      });
    });
  }
  
  function genericValidateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    inputs.forEach(input => {
      if (!genericValidateInput(input)) {
        isValid = false;
      }
    });
    return isValid;
  }
  
  function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
  }
  
  /* Dashboard & Session Functions */
  async function fetchStatistics() {
    try {
      const response = await fetch('/api/statistics');
      if (!response.ok) throw new Error('API response error');
      const data = await response.json();
      updateWidgets(data);
    } catch (error) {
      showErrorToast('Failed to fetch statistics.');
      console.error('Statistics fetch error:', error);
    }
  }
  
  function updateWidgets(data) {
    updateWidget('total-users', data.totalUsers);
    updateWidget('active-sessions', data.activeSessions);
    updateWidget('new-bookings', data.newBookings);
    updateWidget('total-revenue', formatCurrency(data.totalRevenue));
  }
  
  function updateWidget(widgetId, value) {
    const widget = document.getElementById(widgetId);
    if (widget) {
      widget.innerText = value ?? 'No data';
    }
  }
  
  function handleResponsiveUpdates() {
    console.log('Updating UI for screen resize.');
    // Additional responsive UI logic can be added here.
  }
  
  function formatCurrency(amount) {
    return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(amount);
  }
  
  function checkUserActivity() {
    if (document.visibilityState === 'visible') {
      fetchStatistics();
    }
  }
  
  function logout() {
    localStorage.removeItem('auth_token');
    window.location.href = '/login';
  }
  
  function refreshSession() {
    const token = localStorage.getItem('auth_token');
    if (!token) return;
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      const expiration = payload.exp * 1000;
      if (Date.now() >= expiration) {
        logout();
      }
    } catch (error) {
      console.error('Token validation error:', error);
      logout();
    }
  }
  
  /**************************************
   * Main Initialization (DOMContentLoaded)
   **************************************/
  document.addEventListener('DOMContentLoaded', function () {
    ensureToastContainer();
    // Session & dashboard updates
    setInterval(refreshSession, 60000);
    setInterval(checkUserActivity, 60000);
    window.addEventListener('resize', handleResponsiveUpdates);
  
    initAuthModule();
    initBookingModule();
    initDashboardModule();
    initDocumentModule();
    initGenericValidationModule();
    initNotificationsModule();
    initPaymentModule();
    initProfileModule();
    initRegistrationModule();
  });
  
  /**************************************
   * Auth Module
   **************************************/
  function initAuthModule() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);
  }
  
  async function handleLogin(event) {
    event.preventDefault();
    clearGlobalError();
    const formData = new FormData(event.target);
    const username = formData.get('username').trim();
    const password = formData.get('password').trim();
    if (!validateCredentials(username, password)) return;
    try {
      const response = await ajax.post('/login', { username, password });
      if (response.success) {
        ajax.setToken(response.token);
        redirectToDashboard();
      } else {
        displayGlobalError(response.error || 'Login error occurred.');
      }
    } catch (error) {
      console.error('Login error:', error);
      displayGlobalError('An error occurred during login. Please try again.');
    }
  }
  
  async function handleRegister(event) {
    event.preventDefault();
    clearGlobalError();
    const formData = new FormData(event.target);
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
        displayGlobalError(response.error || 'Registration error occurred.');
      }
    } catch (error) {
      console.error('Registration error:', error);
      displayGlobalError('An error occurred during registration. Please try again.');
    }
  }
  
  function redirectToDashboard() {
    window.location.href = '/dashboard';
  }
  
  function validateCredentials(username, password, confirmPassword = null) {
    if (!username || username.length < 3) {
      displayGlobalError('Username must be at least 3 characters long.');
      return false;
    }
    if (!password || password.length < 6) {
      displayGlobalError('Password must be at least 6 characters long.');
      return false;
    }
    if (confirmPassword !== null && password !== confirmPassword) {
      displayGlobalError('Passwords do not match.');
      return false;
    }
    return true;
  }
  
  /**************************************
   * Booking Module
   **************************************/
  function initBookingModule() {
    const pickupDateInput = document.getElementById('pickup-date');
    const bookingForm = document.getElementById('booking-form');
    if (pickupDateInput) pickupDateInput.addEventListener('change', bookingFetchAvailableVehicles);
    if (bookingForm) bookingForm.addEventListener('submit', bookingSubmitRequest);
  }
  
  async function bookingFetchAvailableVehicles() {
    const pickupDateInput = document.getElementById('pickup-date');
    const pickupDate = pickupDateInput ? pickupDateInput.value.trim() : '';
    if (!pickupDate) return;
    bookingShowLoadingIndicator();
    try {
      const response = await fetch(`/vehicles/available?pickup_date=${pickupDate}`);
      const data = await response.json();
      bookingHideLoadingIndicator();
      if (data.vehicles && data.vehicles.length > 0) {
        bookingDisplayAvailableVehicles(data.vehicles);
      } else {
        displayGlobalError('No vehicles available for the selected date.');
      }
    } catch (error) {
      bookingHideLoadingIndicator();
      console.error('Error fetching vehicles:', error);
      displayGlobalError('Failed to fetch available vehicles.');
    }
  }
  
  function bookingDisplayAvailableVehicles(vehicles) {
    const vehiclesContainer = document.getElementById('vehicles-container');
    if (!vehiclesContainer) return;
    vehiclesContainer.innerHTML = '';
    vehicles.forEach(vehicle => {
      const vehicleElement = document.createElement('div');
      vehicleElement.className = 'vehicle';
      vehicleElement.innerHTML = `<p>${vehicle.name}</p><p>${vehicle.type}</p>`;
      vehiclesContainer.appendChild(vehicleElement);
    });
  }
  
  function bookingValidateLocations() {
    const pickupLocation = document.getElementById('pickup-location')?.value.trim();
    const dropoffLocation = document.getElementById('dropoff-location')?.value.trim();
    if (!pickupLocation || !dropoffLocation) {
      displayGlobalError('Both pickup and dropoff locations are required.');
      return false;
    }
    return true;
  }
  
  function bookingValidateForm() {
    let isValid = true;
    const requiredFields = ['pickup-date', 'return-date', 'pickup-location', 'dropoff-location'];
    requiredFields.forEach(field => {
      const input = document.getElementById(field);
      if (!input || !input.value.trim()) {
        displayGlobalError(`The field ${field.replace('-', ' ')} is required.`);
        isValid = false;
      }
    });
    return isValid;
  }
  
  async function bookingSubmitRequest(event) {
    event.preventDefault();
    clearGlobalError();
    if (!bookingValidateLocations() || !bookingValidateForm()) return;
    const bookingForm = document.getElementById('booking-form');
    const formData = new FormData(bookingForm);
    bookingShowLoadingIndicator();
    try {
      const response = await fetch('/booking/create', {
        method: 'POST',
        body: formData
      });
      const data = await response.json();
      bookingHideLoadingIndicator();
      if (data.success) {
        alert('Booking completed successfully!');
        window.location.href = "/bookings/view";
      } else {
        displayGlobalError(data.error || 'There was a problem creating the booking.');
      }
    } catch (error) {
      bookingHideLoadingIndicator();
      console.error('Booking submission error:', error);
      displayGlobalError('Failed to create the booking.');
    }
  }
  
  function bookingShowLoadingIndicator() {
    const loadingIndicator = document.getElementById('loading-indicator');
    if (loadingIndicator) loadingIndicator.style.display = 'block';
  }
  
  function bookingHideLoadingIndicator() {
    const loadingIndicator = document.getElementById('loading-indicator');
    if (loadingIndicator) loadingIndicator.style.display = 'none';
  }
  
  /**************************************
   * Dashboard Module
   **************************************/
  function initDashboardModule() {
    // Initial fetch of statistics
    fetchStatistics();
  }
  
  /**************************************
   * Document Module
   **************************************/
  function initDocumentModule() {
    const uploadForm = document.getElementById('document-upload-form');
    const signButton = document.getElementById('sign-button');
    const uploadInput = document.getElementById('uploadButton');
    if (uploadForm) uploadForm.addEventListener('submit', uploadDocument);
    if (signButton) signButton.addEventListener('click', handleSignButtonClick);
    if (uploadInput) uploadInput.addEventListener('change', handleFileSelection);
  }
  
  async function uploadDocument(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    try {
      const response = await fetch('/api/documents/upload', {
        method: 'POST',
        body: formData,
        headers: { 'Authorization': 'Bearer ' + getAuthToken() }
      });
      const data = await response.json();
      if (data.success) {
        alert('Document uploaded successfully.');
        previewDocument(data.documentUrl);
      } else {
        displayGlobalError('Document upload error: ' + data.message);
      }
    } catch (error) {
      console.error('Document upload error:', error);
      displayGlobalError('An error occurred while uploading the document.');
    }
  }
  
  function handleFileSelection(event) {
    const file = event.target.files[0];
    if (file) previewContract(file);
  }
  
  function previewDocument(documentUrl) {
    const previewFrame = document.getElementById('document-preview');
    if (previewFrame) {
      previewFrame.src = documentUrl;
      previewFrame.style.display = 'block';
    }
  }
  
  function handleSignButtonClick() {
    const documentId = document.getElementById('document-id')?.value.trim();
    if (!documentId) {
      displayGlobalError('No document selected for signing.');
      return;
    }
    signDocument(documentId);
  }
  
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
        alert('Document signed successfully.');
      } else {
        displayGlobalError('Document signing error: ' + data.message);
      }
    } catch (error) {
      console.error('Document signing error:', error);
      displayGlobalError('An error occurred while signing the document.');
    }
  }
  
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
  
  /**************************************
   * Generic Form Validation Module
   **************************************/
  function initGenericValidationModule() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
      form.addEventListener('submit', function (event) {
        if (!genericValidateForm(form)) {
          event.preventDefault();
        }
      });
      attachGenericValidation(form);
    });
  }
  
  /**************************************
   * Notifications Module
   **************************************/
  function initNotificationsModule() {
    fetchNotifications();
  }
  
  async function fetchNotifications() {
    try {
      const notifications = await ajax.get('/notifications');
      if (notifications.length > 0) {
        displayNotifications(notifications);
      } else {
        displayNoNotificationsMessage();
      }
    } catch (error) {
      console.error('Notifications fetch error:', error);
    }
  }
  
  function displayNotifications(notifications) {
    const container = document.getElementById('notifications-container');
    if (!container) return;
    container.innerHTML = '';
    notifications.forEach(notification => {
      const el = document.createElement('div');
      el.className = `notification ${notification.read ? 'read' : 'unread'}`;
      el.innerHTML = `
        <p>${notification.message}</p>
        <button class="mark-as-read" data-id="${notification.id}">Mark as read</button>
      `;
      container.appendChild(el);
    });
    attachMarkAsReadListeners();
  }
  
  function attachMarkAsReadListeners() {
    document.querySelectorAll('.mark-as-read').forEach(button => {
      button.addEventListener('click', function () {
        markAsRead(this.dataset.id);
      });
    });
  }
  
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
        console.error('Error marking notification as read:', data.error);
      }
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  }
  
  function updateNotificationStatus(notificationId) {
    const btn = document.querySelector(`.mark-as-read[data-id="${notificationId}"]`);
    if (btn) {
      btn.closest('.notification').classList.add('read');
      btn.remove();
    }
  }
  
  function displayNoNotificationsMessage() {
    const container = document.getElementById('notifications-container');
    if (container) {
      container.innerHTML = `<p class="text-muted">No new notifications.</p>`;
    }
  }
  
  /**************************************
   * Payment Module
   **************************************/
  function initPaymentModule() {
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
      paymentForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (paymentValidateForm()) {
          const paymentDetails = new FormData(paymentForm);
          processPayment(paymentDetails);
        }
      });
    }
  }
  
  function paymentValidateForm() {
    let isValid = true;
    const cardNumber = document.getElementById('cardNumber')?.value;
    const expiryDate = document.getElementById('expiryDate')?.value;
    const cvv = document.getElementById('cvv')?.value;
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) errorMessage.innerHTML = '';
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
      const response = await ajax.post('/payments', Object.fromEntries(paymentDetails.entries()));
      if (response.success) {
        window.location.href = '/booking/confirmation';
      } else {
        displayPaymentErrors(response.errors);
      }
    } catch (error) {
      displayPaymentErrors(['An error occurred while processing the payment. Please try again.']);
    }
  }
  
  function displayPaymentErrors(errors) {
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
      errorMessage.innerHTML = '';
      errors.forEach(error => {
        errorMessage.innerHTML += `<p>${error}</p>`;
      });
    }
  }
  
  /**************************************
   * Profile Module
   **************************************/
  function initProfileModule() {
    const profileForm = document.getElementById('profile-form');
    const avatarUpload = document.getElementById('avatar-upload');
    if (profileForm) profileForm.addEventListener('submit', updateProfile);
    if (avatarUpload) avatarUpload.addEventListener('change', handleAvatarUpload);
  }
  
  async function updateProfile(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    if (!validateProfileForm(formData)) return;
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
      console.error('Profile update error:', error);
    }
  }
  
  function handleAvatarUpload(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        const preview = document.getElementById('avatar-preview');
        if (preview) preview.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  }
  
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
  
  /**************************************
   * Registration Module (alternate form)
   **************************************/
  function initRegistrationModule() {
    const regForm = document.getElementById('registerForm');
    if (regForm) {
      regForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        try {
          const response = await fetch('/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
          });
          const result = await response.json();
          if (response.ok) {
            showSuccessToast('Registration successful! Welcome to Carfuse.');
            this.reset();
          } else {
            showErrorToast(result.message || 'Registration failed.');
          }
        } catch (error) {
          console.error('Registration error:', error);
          showErrorToast('An unexpected error occurred.');
        }
      });
    }
  }
  