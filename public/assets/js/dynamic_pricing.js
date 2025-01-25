import { fetchData, handleApiError, showAlert } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/dynamic_pricing.js
 * Description: Manages dynamic pricing calculations and updates
 * Changelog:
 * - Refactored to use centralized fetchData utility
 * - Added loading states and error handling
 */

document.addEventListener('DOMContentLoaded', () => {
  const priceForm = document.getElementById('pricingForm');
  const updateButton = document.getElementById('updatePricing');

  // Calculate dynamic price based on vehicle and dates
  async function calculatePrice(vehicleId, dates) {
    try {
      const pricingData = await fetchData('/public/api.php', {
        endpoint: 'pricing',
        method: 'POST',
        body: { action: 'calculate', vehicle_id: vehicleId, dates }
      });
      
      updatePriceDisplay(pricingData);
      return pricingData.adjustedPrice;
    } catch (error) {
      handleApiError(error, 'calculating price');
      return null;
    }
  }

  // Helper to update price display
  function updatePriceDisplay(pricingData) {
    document.getElementById('basePrice').textContent = pricingData.basePrice;
    document.getElementById('adjustedPrice').textContent = pricingData.adjustedPrice;
  }

  // Event handlers
  priceForm?.addEventListener('submit', handlePriceFormSubmit);
  updateButton?.addEventListener('click', handlePricingRuleUpdate);

  async function handlePriceFormSubmit(e) {
    e.preventDefault();
    const formData = new FormData(priceForm);
    await calculatePrice(
      formData.get('vehicleId'),
      {
        start: formData.get('startDate'),
        end: formData.get('endDate')
      }
    );
  }

  async function handlePricingRuleUpdate() {
    const rules = {
      seasonMultiplier: document.getElementById('seasonMultiplier').value,
      demandMultiplier: document.getElementById('demandMultiplier').value,
      weekendRate: document.getElementById('weekendRate').value
    };

    try {
      await fetchData('/public/api.php', {
        endpoint: 'pricing',
        method: 'POST',
        body: { action: 'update_rules', rules }
      });
      
      showAlert('Pricing rules updated successfully', 'success');
    } catch (error) {
      handleApiError(error, 'updating pricing rules');
    }
  }
});
