import { fireEvent, screen, waitFor } from '@testing-library/dom';
import '@testing-library/jest-dom';
import '../../../assets/js/calendar';

describe('Calendar Integration', () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <div id="calendar" data-role="admin"></div>
      <div id="eventModal">
        <div id="modal-title"></div>
        <div id="modal-body"></div>
      </div>
    `;
  });

  it('should initialize calendar with correct configuration', () => {
    expect(global.FullCalendar.Calendar).toHaveBeenCalledWith(
      expect.any(HTMLElement),
      expect.objectContaining({
        initialView: 'dayGridMonth',
        selectable: true,
        editable: true
      })
    );
  });

  it('should handle event clicks', async () => {
    const mockEvent = {
      title: 'Test Event',
      start: new Date('2024-01-01'),
      end: new Date('2024-01-02'),
      extendedProps: { description: 'Test Description' }
    };

    // Simulate event click
    fireEvent(
      screen.getByTestId('calendar'),
      new CustomEvent('eventClick', { detail: { event: mockEvent } })
    );

    await waitFor(() => {
      expect(screen.getByText('Event: Test Event')).toBeInTheDocument();
      expect(screen.getByText('Test Description')).toBeInTheDocument();
    });
  });

  // ...more calendar integration tests...
});
