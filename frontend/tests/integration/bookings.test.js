import { fireEvent, screen, waitFor } from '@testing-library/dom';
import '@testing-library/jest-dom';
import '../../../assets/js/bookings';

describe('Bookings Integration', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="checkAvailability"></div>
      <input id="pickupDate" value="2024-01-01" />
      <input id="dropoffDate" value="2024-01-05" />
      <div id="availableCars"></div>
    `;
  });

  it('should check availability and display cars', async () => {
    const mockCars = [
      { id: 1, make: 'Toyota', model: 'Corolla', price_per_day: 100 }
    ];

    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ cars: mockCars })
    });

    fireEvent.click(screen.getByTestId('checkAvailability'));

    await waitFor(() => {
      expect(screen.getByText('Toyota Corolla')).toBeInTheDocument();
      expect(screen.getByText('Price per day: 100 PLN')).toBeInTheDocument();
    });
  });

  // ...more booking integration tests...
});
