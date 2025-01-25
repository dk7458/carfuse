import '@testing-library/jest-dom';

// Mock browser APIs
global.fetch = jest.fn();
global.bootstrap = {
  Modal: jest.fn().mockImplementation(() => ({
    show: jest.fn(),
    hide: jest.fn()
  }))
};
global.Chart = jest.fn();
