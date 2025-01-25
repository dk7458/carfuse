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

    async function calculatePrice(vehicleId, dates) {
        try {
            const data = await fetchData('/public/api.php', {
                endpoint: 'pricing',
                method: 'POST',
                body: {
                    action: 'calculate',
                    vehicle_id: vehicleId,
                    dates
                }
            });
            
            document.getElementById('basePrice').textContent = data.basePrice;
            document.getElementById('adjustedPrice').textContent = data.adjustedPrice;
            
            return data.adjustedPrice;
        } catch (error) {
            handleApiError(error, 'calculating price');
            return null;
        }
    }

    async function updatePricingRules(rules) {
        try {
            await fetchData('/public/api.php', {
                endpoint: 'pricing',
                method: 'POST',
                body: {
                    action: 'update_rules',
                    rules
                }
            });
            
            showAlert('Pricing rules updated successfully', 'success');
        } catch (error) {
            handleApiError(error, 'updating pricing rules');
        }
    }

    // Event listeners
    priceForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(priceForm);
        await calculatePrice(formData.get('vehicleId'), {
            start: formData.get('startDate'),
            end: formData.get('endDate')
        });
    });

    updateButton?.addEventListener('click', async () => {
        const rules = {
            seasonMultiplier: document.getElementById('seasonMultiplier').value,
            demandMultiplier: document.getElementById('demandMultiplier').value,
            weekendRate: document.getElementById('weekendRate').value
        };
        await updatePricingRules(rules);
    });
});
