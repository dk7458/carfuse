import { fetchData, handleApiError, showAlert } from '@/shared/utils';

describe('Shared Utilities', () => {
  beforeEach(() => {
    // Clear all mocks
    jest.clearAllMocks();
    document.body.innerHTML = '';
  });

  describe('fetchData', () => {
    it('should fetch data successfully', async () => {
      const mockResponse = { data: 'test' };
      global.fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse)
      });

      const result = await fetchData('/test-endpoint', {
        method: 'GET',
        params: { test: 'param' }
      });

      expect(result).toEqual(mockResponse);
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/test-endpoint'),
        expect.any(Object)
      );
    });

    it('should handle API errors', async () => {
      global.fetch.mockRejectedValueOnce(new Error('API Error'));
      const consoleSpy = jest.spyOn(console, 'error');

      await expect(fetchData('/test-endpoint')).rejects.toThrow('API Error');
      expect(consoleSpy).toHaveBeenCalled();
    });
  });

  // ...more utility tests...
});
