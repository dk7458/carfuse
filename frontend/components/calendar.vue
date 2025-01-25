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
        events: this.eventsUrl,
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

    handleEventDrop(info) {
      if (this.userRole === 'admin') {
        this.$emit('event-drop', {
          event: info.event,
          oldEvent: info.oldEvent
        })
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
