<template>
  <div class="calendar-wrapper">
    <slot name="header"></slot>
    
    <FullCalendar
      class="calendar"
      :options="calendarOptions"
      ref="fullCalendar"
    />
    
    <slot name="footer"></slot>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import '@fullcalendar/core/vdom'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import { fetchData, handleError } from '../shared/utils'

/**
 * @component Calendar
 * @description Calendar component with dynamic event loading
 * 
 * @api {GET} /api/events - Fetch events
 * @apiParam {string} start - Start date
 * @apiParam {string} end - End date
 * 
 * @api {POST} /api/events - Create event
 * @api {PUT} /api/events/:id - Update event
 * @api {DELETE} /api/events/:id - Delete event
 */
export default defineComponent({
  name: 'Calendar',
  components: {
    FullCalendar
  },
  props: {
    // Controls editing capabilities based on user role
    userRole: {
      type: String,
      required: true,
      validator: (value) => ['admin', 'user'].includes(value)
    },
    // API endpoint for fetching events
    eventsUrl: {
      type: String,
      required: true
    }
  },

  data() {
    return {
      calendarOptions: {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: this.userRole === 'admin',
        selectable: this.userRole === 'admin',
        events: async (info, successCallback, failureCallback) => {
          try {
            const events = await fetchData(`${this.eventsUrl}?start=${info.startStr}&end=${info.endStr}`);
            successCallback(events);
          } catch (error) {
            handleError(error, 'Calendar');
            failureCallback(error);
          }
        },
        eventClick: this.handleEventClick,
        dateClick: this.handleDateClick,
        eventDrop: this.handleEventDrop,
        eventResize: this.handleEventResize,
        height: 'auto'
      }
    }
  },

  methods: {
    handleEventClick(info) {
      this.$emit('event-click', info.event)
    },

    handleDateClick(info) {
      if (this.userRole === 'admin') {
        this.$emit('date-click', info)
      }
    },

    async handleEventDrop(info) {
      if (this.userRole !== 'admin') return;

      try {
        await fetchData(`/api/events/${info.event.id}`, {
          method: 'PUT',
          body: JSON.stringify({
            start: info.event.startStr,
            end: info.event.endStr
          }),
          noCache: true
        });
      } catch (error) {
        handleError(error, 'Calendar');
        info.revert();
      }
    },

    handleEventResize(info) {
      if (this.userRole === 'admin') {
        this.$emit('event-resize', {
          event: info.event,
          oldEvent: info.oldEvent
        })
      }
    },

    refreshEvents() {
      const calendarApi = this.$refs.fullCalendar.getApi()
      calendarApi.refetchEvents()
    }
  }
})
</script>

<style scoped>
.calendar-wrapper {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.calendar {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
  .calendar-wrapper {
    padding: 0.5rem;
  }
  
  :deep(.fc-toolbar) {
    flex-direction: column;
    gap: 0.5rem;
  }

  :deep(.fc-toolbar-title) {
    font-size: 1.2em !important;
  }

  :deep(.fc-button) {
    padding: 0.2rem 0.5rem !important;
  }
}
</style>
