<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-primary fw-bold">
        <i class="bi bi-calendar-range me-2"></i>Booking Calendar
      </h5>
      <div>
        <select v-model="selectedGround" class="form-select form-select-sm d-inline-block w-auto me-2" @change="fetchBookings">
          <option value="">All Grounds</option>
          <option v-for="ground in grounds" :key="ground.id" :value="ground.id">{{ ground.name }}</option>
        </select>
        <button class="btn btn-primary btn-sm rounded-pill px-3" @click="openBookingModal()">
          <i class="bi bi-plus-circle me-1"></i> New Booking
        </button>
      </div>
    </div>
    <div class="card-body">
      <FullCalendar ref="fullCalendar" :options="calendarOptions" />
    </div>

    <!-- Booking Modal Placeholders (We will implement the modal component next) -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold text-primary">Create Booking</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-4 text-center">
            <!-- Modal form will be injected here -->
            <h6 class="text-muted">Booking form for {{ selectedSlot?.startStr }} to {{ selectedSlot?.endStr }}</h6>
            <button class="btn btn-secondary mt-3" @click="closeModal">Close for now</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const grounds = ref([]);
const selectedGround = ref('');
const bookings = ref([]);
const showModal = ref(false);
const selectedSlot = ref(null);
const fullCalendar = ref(null);

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'timeGridWeek',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  slotMinTime: '06:00:00',
  slotMaxTime: '24:00:00',
  allDaySlot: false,
  selectable: true,
  selectMirror: true,
  select: handleDateSelect,
  eventClick: handleEventClick,
  events: [],
  height: 'auto',
  eventTimeFormat: {
    hour: 'numeric',
    minute: '2-digit',
    meridiem: 'short'
  }
});

const fetchGrounds = async () => {
  try {
    const { data } = await axios.get('/api/grounds?all=true');
    grounds.value = data;
  } catch (error) {
    console.error('Error fetching grounds', error);
  }
};

const fetchBookings = async () => {
  try {
    // We will build a specific calendar API endpoint or format this from index
    const { data } = await axios.get('/api/bookings');
    
    // Transform into FullCalendar event format
    const events = [];
    data.data.forEach(booking => {
      booking.slots.forEach(slot => {
        let color = '#3788d8'; // default primary
        if (booking.status === 'confirmed') color = '#198754';
        if (booking.status === 'running') color = '#0dcaf0';
        if (booking.status === 'completed') color = '#6c757d';
        
        events.push({
          id: booking.id,
          title: booking.client.name + ' (' + booking.ground.name + ')',
          start: slot.date + 'T' + slot.start_time,
          end: slot.date + 'T' + slot.end_time,
          backgroundColor: color,
          borderColor: color,
          extendedProps: { booking }
        });
      });
    });
    
    // Filter by ground if selected
    calendarOptions.value.events = selectedGround.value 
      ? events.filter(e => e.extendedProps.booking.ground_id == selectedGround.value)
      : events;
      
  } catch (error) {
    console.error('Error fetching bookings', error);
  }
};

function handleDateSelect(selectInfo) {
  selectedSlot.value = selectInfo;
  showModal.value = true;
  selectInfo.view.calendar.unselect();
}

function handleEventClick(clickInfo) {
  alert('Event clicked: ' + clickInfo.event.title + '\nStatus: ' + clickInfo.event.extendedProps.booking.status);
}

function openBookingModal() {
  selectedSlot.value = null;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedSlot.value = null;
}

onMounted(() => {
  fetchGrounds();
  fetchBookings();
});
</script>

<style scoped>
/* Add any custom styles for fullcalendar overrides */
:deep(.fc-theme-standard .fc-scrollgrid) {
  border-color: #dee2e6;
}
:deep(.fc-v-event) {
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
:deep(.fc-toolbar-title) {
  font-size: 1.25rem !important;
  font-weight: 700;
  color: #2c3e50;
}
:deep(.fc-button-primary) {
  background-color: #0d6efd !important;
  border-color: #0d6efd !important;
}
:deep(.fc-button-primary:hover) {
  background-color: #0b5ed7 !important;
  border-color: #0a58ca !important;
}
:deep(.fc-button-active) {
  background-color: #0a58ca !important;
  border-color: #0a53be !important;
}
</style>
