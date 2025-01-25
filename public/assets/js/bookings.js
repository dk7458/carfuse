import { fetchData, handleApiError, showAlert } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/bookings.js
 * Description: Manages booking operations
 * Changelog:
 * - Refactored to use centralized fetchData utility
 * - Added loading indicators and error handling
 */

document.addEventListener('DOMContentLoaded', () => {
  initializeBookingHandlers();
  initializeAdminHandlers();
});

function initializeBookingHandlers() {
  const checkAvailabilityButton = document.getElementById('checkAvailability');
  const confirmBookingButton = document.getElementById('confirmBooking');

  checkAvailabilityButton?.addEventListener('click', handleAvailabilityCheck);
  confirmBookingButton?.addEventListener('click', handleBookingConfirmation);
}

async function handleAvailabilityCheck(e) {
  e.preventDefault();
  try {
    const dateRange = getDateRange();
    const availabilityData = await fetchAvailableCars(dateRange);
    renderAvailableCars(availabilityData.cars);
  } catch (error) {
    handleApiError(error, 'checking availability');
  }
}

function getDateRange() {
  return {
    pickupDate: document.getElementById('pickupDate').value,
    dropoffDate: document.getElementById('dropoffDate').value
  };
}

async function fetchAvailableCars(dateRange) {
  return await fetchData('/public/api.php', {
    endpoint: 'booking',
    method: 'POST',
    body: {
      action: 'check_availability',
      pickup_date: dateRange.pickupDate,
      dropoff_date: dateRange.dropoffDate
    }
  });
}

function renderAvailableCars(cars) {
  const carsContainer = document.getElementById('availableCars');
  carsContainer.innerHTML = cars.map(car => `
    <div class="car-tile">
      <img src="${car.image_path}" alt="${car.make} ${car.model}">
      <h3>${car.make} ${car.model}</h3>
      <p>Price per day: ${car.price_per_day} PLN</p>
      <button class="select-car" data-id="${car.id}">Select</button>
    </div>
  `).join('');
}

async function handleBookingConfirmation(e) {
  e.preventDefault();
  try {
    const bookingDetails = getBookingDetails();
    const bookingData = await createBooking(bookingDetails);
    redirectToConfirmation(bookingData.booking_id);
  } catch (error) {
    handleApiError(error, 'creating booking');
  }
}

function getBookingDetails() {
  return {
    vehicleId: document.getElementById('vehicleId').value,
    pickupDate: document.getElementById('pickupDate').value,
    dropoffDate: document.getElementById('dropoffDate').value,
    totalPrice: document.getElementById('totalPrice').textContent
  };
}

async function createBooking(details) {
  return await fetchData('/public/api.php', {
    endpoint: 'booking',
    method: 'POST',
    body: {
      action: 'create_booking',
      vehicle_id: details.vehicleId,
      pickup_date: details.pickupDate,
      dropoff_date: details.dropoffDate,
      total_price: details.totalPrice
    }
  });
}

function redirectToConfirmation(bookingId) {
  window.location.href = `/views/user/booking_confirmation.php?booking_id=${bookingId}`;
}

function initializeAdminHandlers() {
  document.querySelectorAll('.refund-button').forEach(button => {
    button.addEventListener('click', handleRefund);
  });
}

async function handleRefund(e) {
  const button = e.currentTarget;
  const bookingId = button.dataset.id;
  const refundAmount = button.dataset.amount;

  if (confirm(`Confirm refund of ${refundAmount} PLN?`)) {
    try {
      const refundData = await processRefund(bookingId, refundAmount);
      showAlert('Refund processed successfully', 'success');
      location.reload();
    } catch (error) {
      handleApiError(error, 'processing refund');
    }
  }
}

async function processRefund(bookingId, refundAmount) {
  return await fetchData('/public/api.php', {
    endpoint: 'booking_manager',
    method: 'POST',
    body: {
      action: 'refund',
      booking_id: bookingId,
      refund_amount: refundAmount
    }
  });
}
