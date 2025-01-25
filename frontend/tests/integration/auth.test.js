import { fireEvent, screen, waitFor } from '@testing-library/dom';
import '@testing-library/jest-dom';
import '../../../assets/js/auth';

describe('Auth Integration', () => {
  const mockLocalStorage = {};

  beforeEach(() => {
    document.body.innerHTML = `
      <button id="logout">Logout</button>
    `;
    
    Object.defineProperty(window, 'localStorage', {
      value: {
        getItem: jest.fn(key => mockLocalStorage[key]),
        setItem: jest.fn((key, value) => mockLocalStorage[key] = value),
        removeItem: jest.fn(key => delete mockLocalStorage[key])
      },
      writable: true
    });
  });

  it('should handle logout correctly', async () => {
    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ success: true })
    });

    fireEvent.click(screen.getByText('Logout'));

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/public/api.php'),
        expect.objectContaining({
          method: 'POST',
          body: expect.stringContaining('logout')
        })
      );
      expect(window.location.href).toBe('/login.php');
    });
  });

  // ...more auth integration tests...
});
