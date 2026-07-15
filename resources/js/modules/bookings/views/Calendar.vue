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
          <div class="modal-body p-4">
            <form @submit.prevent="submitBooking">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold text-secondary">Select Client</label>
                  <select v-model="form.client_id" class="form-select" required>
                    <option value="">-- Choose Client --</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }} ({{ client.phone }})</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold text-secondary">Select Ground</label>
                  <select v-model="form.ground_id" class="form-select" @change="calculatePricePreview" required>
                    <option value="">-- Choose Ground --</option>
                    <option v-for="ground in activeGrounds" :key="ground.id" :value="ground.id">{{ ground.name }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-secondary">Date</label>
                  <input type="date" v-model="form.date" class="form-control" @change="calculatePricePreview" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-secondary">Start Time</label>
                  <input type="time" v-model="form.start_time" class="form-control" @change="calculatePricePreview" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-secondary">End Time</label>
                  <input type="time" v-model="form.end_time" class="form-control" @change="calculatePricePreview" required>
                </div>
                
                <!-- Live Price Preview Box -->
                <div class="col-12" v-if="pricePreview">
                  <div class="alert alert-info border-0 shadow-sm mb-0">
                    <h6 class="fw-bold mb-2"><i class="bi bi-calculator me-2"></i>Price Calculation</h6>
                    <div class="d-flex justify-content-between mb-1 text-sm">
                      <span>Base Price ({{ pricePreview.duration_hours }} hrs):</span>
                      <span class="fw-bold">৳ {{ pricePreview.base_price }}</span>
                    </div>
                    <div v-for="rule in pricePreview.applied_rules" :key="rule.name" class="d-flex justify-content-between mb-1 text-sm text-primary">
                      <span>+ {{ rule.name }} ({{ rule.type }}):</span>
                      <span class="fw-bold">৳ {{ rule.modifier }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between text-dark">
                      <span class="fw-bold">Total Amount:</span>
                      <span class="fw-bold fs-5">৳ {{ pricePreview.total_price }}</span>
                    </div>
                  </div>
                </div>

                <!-- Error Box -->
                <div class="col-12" v-if="priceError">
                  <div class="alert alert-danger border-0 shadow-sm mb-0 text-sm">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ priceError }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-bold text-secondary">Discount (৳)</label>
                  <input type="number" v-model="form.discount" class="form-control" min="0" :max="pricePreview?.total_price || 0">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold text-secondary">Paid Amount (৳)</label>
                  <input type="number" v-model="form.paid_amount" class="form-control" min="0">
                </div>
                
                <div class="col-12 mt-4 text-end">
                  <button type="button" class="btn btn-light me-2" @click="closeModal">Cancel</button>
                  <button type="submit" class="btn btn-primary px-4" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check-circle me-1"></i> Confirm Booking
                  </button>
                </div>
              </div>
            </form>
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
const activeGrounds = ref([]);
const clients = ref([]);
const selectedGround = ref('');
const bookings = ref([]);
const showModal = ref(false);
const selectedSlot = ref(null);
const fullCalendar = ref(null);
const isSubmitting = ref(false);
const pricePreview = ref(null);
const priceError = ref(null);

const form = ref({
  client_id: '',
  ground_id: '',
  date: '',
  start_time: '',
  end_time: '',
  discount: 0,
  paid_amount: 0
});

// ... existing calendarOptions ...
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

const fetchClients = async () => {
  try {
    const { data } = await axios.get('/api/clients');
    clients.value = data.data || data;
  } catch (error) {
    console.error('Error fetching clients', error);
  }
};

const fetchGrounds = async () => {
  try {
    const { data } = await axios.get('/api/grounds?all=true');
    grounds.value = data;
    activeGrounds.value = data.filter(g => g.status === 'active');
  } catch (error) {
    console.error('Error fetching grounds', error);
  }
};

const fetchBookings = async () => {
  try {
    // We will build a specific calendar API endpoint or format this from index
    const { data } = await axios.get('/api/bookings?all=true');
    
    // Transform into FullCalendar event format
    const events = [];
    data.forEach(booking => {
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
  
  // Format dates for the form
  const start = selectInfo.start;
  const end = selectInfo.end;
  
  form.value = {
    client_id: '',
    ground_id: selectedGround.value || '',
    date: start.toISOString().split('T')[0],
    start_time: start.toTimeString().substring(0,5),
    end_time: end.toTimeString().substring(0,5),
    discount: 0,
    paid_amount: 0
  };
  
  pricePreview.value = null;
  if (form.value.ground_id) calculatePricePreview();
  
  showModal.value = true;
  selectInfo.view.calendar.unselect();
}

const calculatePricePreview = async () => {
  priceError.value = null;
  if (!form.value.ground_id || !form.value.date || !form.value.start_time || !form.value.end_time) {
    pricePreview.value = null;
    return;
  }
  
  try {
    const { data } = await axios.post('/api/bookings/calculate-price', {
      ground_id: form.value.ground_id,
      date: form.value.date,
      start_time: form.value.start_time,
      end_time: form.value.end_time
    });
    pricePreview.value = data;
  } catch (error) {
    console.error('Price calculation failed', error);
    pricePreview.value = null;
    priceError.value = error.response?.data?.errors?.time_slot?.[0] || 'Invalid time slot selected.';
  }
};

const submitBooking = async () => {
  isSubmitting.value = true;
  try {
    await axios.post('/api/bookings', form.value);
    alert('Booking created successfully!');
    closeModal();
    fetchBookings(); // Refresh calendar
  } catch (error) {
    const msg = error.response?.data?.errors?.time_slot?.[0] || 
                error.response?.data?.message || 
                'Failed to create booking';
    alert('Error: ' + msg);
  } finally {
    isSubmitting.value = false;
  }
};

function handleEventClick(clickInfo) {
  alert('Event clicked: ' + clickInfo.event.title + '\nStatus: ' + clickInfo.event.extendedProps.booking.status);
}

function openBookingModal() {
  form.value = {
    client_id: '',
    ground_id: selectedGround.value || '',
    date: new Date().toISOString().split('T')[0],
    start_time: '10:00',
    end_time: '11:00',
    discount: 0,
    paid_amount: 0
  };
  pricePreview.value = null;
  selectedSlot.value = null;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedSlot.value = null;
}

onMounted(() => {
  fetchClients();
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
