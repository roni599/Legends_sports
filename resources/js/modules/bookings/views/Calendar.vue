<template>
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
      <h5 class="mb-0 text-primary fw-bold">
        <i class="bi bi-calendar-range me-2"></i>Booking Calendar
      </h5>
      <div>
        <select v-model="selectedGround" class="form-select form-select-sm d-inline-block w-auto me-2" @change="applyFilter">
          <option value="">All Grounds</option>
          <option v-for="ground in grounds" :key="ground.id" :value="ground.id">{{ ground.name }}</option>
        </select>
        <router-link to="/bookings/create" class="btn btn-primary btn-sm rounded-pill px-3">
          <i class="bi bi-plus-circle me-1"></i> New Booking
        </router-link>
      </div>
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap gap-3 mb-3 small">
        <span><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#ffc107"></span>Pending</span>
        <span><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#0d6efd"></span>Confirmed</span>
        <span><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#0dcaf0"></span>Running</span>
        <span><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#198754"></span>Completed</span>
        <span><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#dc3545"></span>Cancelled</span>
        <span><span class="d-inline-block rounded-circle me-1" style="width:12px;height:12px;background:#6c757d"></span>No Show</span>
      </div>
      <FullCalendar ref="fullCalendar" :options="calendarOptions" />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import axios from 'axios';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const router = useRouter();
const grounds = ref([]);
const selectedGround = ref('');
const fullCalendar = ref(null);
const allBookings = ref([]);

function handleDateSelect(selectInfo) {
  const start = selectInfo.start;
  if (start < new Date()) {
    selectInfo.view.calendar.unselect();
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Cannot book past dates or times!', showConfirmButton: false, timer: 3000 });
    return;
  }
  const bdStr = start.toLocaleString('sv-SE', { timeZone: 'Asia/Dhaka' });
  const query = {
    date: bdStr.split(' ')[0],
    start_time: bdStr.split(' ')[1].substring(0, 5),
    ground_id: selectedGround.value || ''
  };
  selectInfo.view.calendar.unselect();
  router.push({ path: '/bookings/create', query });
}

const calendarOptions = {
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'timeGridWeek',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  slotDuration: '01:00:00',
  slotLabelInterval: '01:00:00',
  snapDuration: '01:00:00',
  slotMinTime: '00:00:00',
  slotMaxTime: '24:00:00',
  allDaySlot: false,
  selectable: true,
  selectMirror: true,
  selectAllow: () => true,
  dayCellClassNames: (arg) => {
    const today = new Date();
    today.setHours(0,0,0,0);
    return arg.date < today ? 'fc-day-disabled' : '';
  },
  slotLaneClassNames: (arg) => {
    return arg.isPast ? 'fc-slot-disabled' : '';
  },
  select: handleDateSelect,
  eventClick: handleEventClick,
  events: [],
  height: 'auto',
  eventDisplay: 'block',
  eventTimeFormat: {
    hour: 'numeric',
    minute: '2-digit',
    meridiem: 'short'
  },
  eventContent: (arg) => {
    const parts = arg.timeText ? [arg.event.title.split(' | ')] : [arg.event.title.split(' | ')];
    const t = arg.event.title.split(' | ');
    return {
      html: `<div style="font-size:0.75rem;line-height:1.4;padding:2px 4px;white-space:normal;">
        <div style="font-weight:700;">${t[0] || ''}</div>
        <div style="font-size:0.7rem;opacity:0.9;">${t[1] || ''}</div>
        <div style="font-size:0.7rem;">${t[2] || ''}</div>
        <div style="font-size:0.65rem;font-weight:600;text-transform:uppercase;">${t[3] || ''}</div>
      </div>`
    };
  }
};

const fetchGrounds = async () => {
  try {
    const { data } = await axios.get('/api/grounds?all=true');
    grounds.value = data;
  } catch (error) {
    console.error('Error fetching grounds', error);
  }
};

const buildEvents = (bookings) => {
  const statusColors = {
    pending: '#ffc107',
    confirmed: '#0d6efd',
    running: '#0dcaf0',
    completed: '#198754',
    cancelled: '#dc3545',
    no_show: '#6c757d'
  };
  const events = [];
  bookings.forEach(booking => {
    booking.slots.forEach(slot => {
      let color = statusColors[booking.status] || '#6c757d';
      const startTime = slot.start_time.substring(0, 5);
      const endTime = slot.end_time.substring(0, 5);
      events.push({
        id: `booking-${booking.id}-slot-${slot.id}`,
        title: booking.client.name + ' | ' + startTime + ' - ' + endTime + ' | ' + booking.ground.name + ' | ' + booking.status.toUpperCase(),
        start: slot.date + 'T' + slot.start_time,
        end: slot.date + 'T' + slot.end_time,
        backgroundColor: color,
        borderColor: color,
        textColor: '#ffffff',
        extendedProps: { booking }
      });
    });
  });
  return events;
};

const updateCalendarEvents = () => {
  const events = buildEvents(allBookings.value);
  const filtered = selectedGround.value
    ? events.filter(e => e.extendedProps.booking.ground_id == selectedGround.value)
    : events;
  const api = fullCalendar.value?.getApi();
  if (api) {
    api.removeAllEvents();
    api.addEventSource(filtered);
  }
};

const fetchBookings = async () => {
  try {
    const { data } = await axios.get('/api/bookings?all=true');
    allBookings.value = data;
    updateCalendarEvents();
  } catch (error) {
    console.error('Error fetching bookings', error);
  }
};

function handleEventClick(clickInfo) {
  const booking = clickInfo.event.extendedProps.booking;
  const statusColor = {
    pending: 'warning', confirmed: 'success', running: 'info',
    completed: 'secondary', cancelled: 'danger', no_show: 'secondary'
  };
  const canComplete = ['pending', 'confirmed', 'running'].includes(booking.status);

  let html = `
    <div class="text-start">
      <p><strong>Client:</strong> ${booking.client?.name}</p>
      <p><strong>Ground:</strong> ${booking.ground?.name}</p>
      <p><strong>Status:</strong> <span class="badge bg-${statusColor[booking.status] || 'secondary'}">${booking.status.toUpperCase()}</span></p>
      <p><strong>Total:</strong> ৳${booking.total_amount} | <strong>Paid:</strong> ৳${booking.paid_amount} | <strong>Due:</strong> ৳${booking.due_amount}</p>
    </div>`;

  Swal.fire({
    title: `Booking #${booking.id}`,
    html: html,
    icon: 'info',
    showCancelButton: canComplete,
    showDenyButton: true,
    confirmButtonColor: '#198754',
    denyButtonColor: '#0d6efd',
    cancelButtonColor: '#6c757d',
    confirmButtonText: canComplete ? '<i class="bi bi-check-circle me-1"></i> Complete' : 'OK',
    denyButtonText: '<i class="bi bi-printer me-1"></i> Print',
    customClass: { popup: 'swal-dark' }
  }).then(async (result) => {
    if (result.isConfirmed && canComplete) {
      try {
        await axios.put(`/api/bookings/${booking.id}`, { status: 'completed' });
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Booking completed!', showConfirmButton: false, timer: 3000 });
        fetchBookings();
      } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Failed to complete', 'error');
      }
    } else if (result.isDenied) {
      window.open(`/print-invoice/${booking.id}`, '_blank', 'width=800,height=600');
    }
  });
}

const applyFilter = () => {
  updateCalendarEvents();
};

onMounted(() => {
  fetchGrounds();
  fetchBookings();
});
</script>

<style scoped>
:deep(.fc-theme-standard .fc-scrollgrid) {
  border-color: #dee2e6;
}
:deep(.fc-v-event) {
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
:deep(.fc-event) {
  border: none !important;
}
:deep(.fc-event-main) {
  color: #fff !important;
  padding: 2px 4px;
}
:deep(.fc-daygrid-event) {
  border: none !important;
  padding: 3px 6px;
  font-size: 0.8rem;
  border-radius: 6px;
}
:deep(.fc-daygrid-day.fc-day-disabled) {
  cursor: not-allowed !important;
}
:deep(.fc-daygrid-day.fc-day-disabled .fc-daygrid-day-frame) {
  opacity: 0.5;
}
:deep(.fc-slot-disabled) {
  cursor: not-allowed !important;
}
:deep(.fc-timegrid-slot-lane) {
  padding: 8px 4px;
}
:deep(.fc-timegrid-slot) {
  height: 60px;
}
:deep(.fc-timegrid-col) {
  padding: 4px 2px;
}
:deep(.fc-v-event) {
  padding: 4px 6px;
  border-radius: 6px;
  font-size: 0.8rem;
  line-height: 1.3;
}
:deep(.fc-event-title) {
  white-space: normal;
  overflow: hidden;
}
:deep(.fc-daygrid-event) {
  padding: 3px 6px;
  font-size: 0.8rem;
  border-radius: 6px;
}
:deep(.fc-daygrid-event-harness) {
  margin-bottom: 2px;
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
