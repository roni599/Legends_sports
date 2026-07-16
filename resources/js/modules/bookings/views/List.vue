<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1 text-primary fw-bold">Bookings Management</h4>
        <p class="text-muted mb-0">View and manage all arena bookings</p>
      </div>
      <div>
        <router-link :to="{ name: 'BookingCalendar' }" class="btn btn-outline-primary me-2">
          <i class="bi bi-calendar3 me-1"></i> Calendar View
        </router-link>
      </div>
    </div>

    <div class="content-card">
      <div class="p-3 border-bottom border-secondary">
        <div class="row g-3 align-items-center">
          <div class="col-md-4">
            <div class="input-group">
              <input type="text" class="form-control custom-input" placeholder="Search by ID, Client name or Phone..." v-model="searchQuery" @input="debounceSearch">
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-select custom-input" v-model="statusFilter" @change="fetchBookings()">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="running">Running</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
              <option value="no_show">No Show</option>
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" class="form-control custom-input" v-model="dateFilter" @change="fetchBookings()">
          </div>
          <div class="col-md-2 text-end">
            <button class="btn btn-light" @click="resetFilters" v-if="searchQuery || statusFilter || dateFilter">
              Clear Filters
            </button>
          </div>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle mb-0">
          <thead>
            <tr>
              <th class="ps-4">SL</th>
              <th>Client</th>
              <th>Ground</th>
              <th>Date & Time</th>
              <th>Financials</th>
              <th>Status</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="7" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Loading bookings...</div>
              </td>
            </tr>
            <tr v-else-if="bookings.length === 0">
              <td colspan="7" class="text-center py-5 text-white">
                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                No bookings found matching your criteria.
              </td>
            </tr>
            <tr v-else v-for="(booking, index) in bookings" :key="booking.id">
              <td class="ps-4 fw-bold text-secondary">{{ (pagination.current_page - 1) * 10 + index + 1 }}</td>
              <td>
                <div class="fw-bold">{{ booking.client?.name }}</div>
                <small class="text-muted d-flex align-items-center mt-1">
                  <i class="bi bi-telephone-fill me-1"></i>{{ booking.client?.phone }}
                  <a v-if="booking.client?.phone" 
                     :href="'https://wa.me/' + (booking.client.phone.startsWith('0') ? '88' + booking.client.phone : booking.client.phone).replace(/[^0-9]/g, '') + '?text=' + encodeURIComponent(`Hello ${booking.client.name}, this is regarding your booking #${booking.id} at ${booking.ground?.name} in Legends Multi Sports Arena.`)" 
                     target="_blank" 
                     class="text-success ms-2" 
                     title="Quick WhatsApp Message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                  </a>
                </small>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ booking.ground?.name }}</span>
              </td>
              <td>
                <div v-for="slot in booking.slots" :key="slot.id">
                  <div class="fw-bold">{{ formatDate(slot.date) }}</div>
                  <small class="text-primary">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</small>
                </div>
              </td>
              <td>
                <div class="text-sm">Total: <strong>৳{{ booking.total_amount }}</strong></div>
                <div class="text-sm text-success">Paid: ৳{{ booking.paid_amount }}</div>
                <div class="text-sm text-danger" v-if="booking.due_amount > 0">
                  Due: ৳{{ booking.due_amount }}
                  <a v-if="booking.client?.phone" 
                     :href="'https://wa.me/' + (booking.client.phone.startsWith('0') ? '88' + booking.client.phone : booking.client.phone).replace(/[^0-9]/g, '') + '?text=' + encodeURIComponent(`Dear ${booking.client.name}, you have a pending due of ৳${booking.due_amount} for your booking #${booking.id} at Legends Multi Sports Arena. Please settle it soon.`)" 
                     target="_blank" 
                     class="btn btn-sm btn-link text-success p-0 ms-2 text-decoration-none" 
                     title="Send Due Reminder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg> Reminder
                  </a>
                </div>
              </td>
              <td>
                <span class="badge rounded-pill" :class="getStatusBadgeClass(booking.status)">
                  {{ booking.status.toUpperCase() }}
                </span>
              </td>
              <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-secondary me-2" @click="printInvoice(booking.id)">
                  <i class="bi bi-printer"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary" @click="openManageModal(booking)">
                  Manage
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="d-flex justify-content-between align-items-center p-3 mt-2" v-if="pagination && pagination.total > 0">
        <div class="text-secondary small mb-2 mb-md-0">
          Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} entries
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="pagination.current_page === 1" @click="fetchBookings(pagination.current_page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="pagination.current_page === pagination.last_page" @click="fetchBookings(pagination.current_page + 1)">Next</button>
        </div>
      </div>
    </div>

    <!-- Manage Booking Modal -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold">Manage Booking #{{ selectedBooking?.id }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body p-4">
            <!-- Receive Payment -->
            <div class="mb-4" v-if="selectedBooking?.due_amount > 0 && selectedBooking?.status !== 'cancelled'">
              <h6 class="fw-bold text-success mb-3"><i class="bi bi-cash me-2"></i>Receive Payment</h6>
              <div class="input-group">
                <span class="input-group-text">৳</span>
                <input type="number" class="form-control" v-model="paymentAmount" :max="selectedBooking?.due_amount">
                <button class="btn btn-success" @click="submitPayment" :disabled="isSubmitting || paymentAmount <= 0 || paymentAmount > selectedBooking?.due_amount">
                  Pay Now
                </button>
              </div>
              <small class="text-muted mt-1 d-block">Max due: ৳{{ selectedBooking?.due_amount }}</small>
            </div>

            <!-- Change Status -->
            <div class="mb-4">
              <h6 class="fw-bold text-primary mb-3"><i class="bi bi-arrow-repeat me-2"></i>Change Status</h6>
              <select class="form-select" v-model="newStatus" @change="submitStatusChange">
                <option :value="selectedBooking?.status" disabled>Current: {{ selectedBooking?.status.toUpperCase() }}</option>
                <option value="pending" v-if="selectedBooking?.status === 'confirmed'">Pending</option>
                <option value="confirmed" v-if="selectedBooking?.status === 'pending'">Confirmed</option>
                <option value="running" v-if="['confirmed', 'pending'].includes(selectedBooking?.status)">Running</option>
                <option value="completed" v-if="selectedBooking?.status === 'running'">Completed</option>
                <option value="cancelled" v-if="!['completed', 'running', 'cancelled'].includes(selectedBooking?.status)">Cancelled</option>
                <option value="no_show" v-if="['pending', 'confirmed'].includes(selectedBooking?.status)">No Show</option>
              </select>
            </div>

            <!-- Cancellation & Refund Logic -->
            <div class="mb-3 p-3 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25" v-if="newStatus === 'cancelled'">
              <h6 class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Cancellation Warning</h6>
              <p class="text-sm text-danger mb-2">Cancelling this booking will forgive the remaining due amount of ৳{{ selectedBooking?.due_amount }}.</p>
              
              <div v-if="selectedBooking?.paid_amount > 0">
                <label class="form-label text-danger fw-bold">Refund Amount (Max: ৳{{ selectedBooking?.paid_amount }})</label>
                <input type="number" class="form-control border-danger" v-model="refundAmount" min="0" :max="selectedBooking?.paid_amount">
              </div>
            </div>
            
            <button v-if="newStatus === 'cancelled'" class="btn btn-danger w-100 fw-bold" @click="submitCancellation" :disabled="isSubmitting">
              Confirm Cancellation
            </button>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const bookings = ref([]);
const isLoading = ref(true);
const searchQuery = ref('');
const statusFilter = ref('');
const dateFilter = ref('');
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 10, from: 0, to: 0 });
let searchTimeout = null;

const showModal = ref(false);
const selectedBooking = ref(null);
const paymentAmount = ref(0);
const newStatus = ref('');
const refundAmount = ref(0);
const isSubmitting = ref(false);

const fetchBookings = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = { page };
    if (searchQuery.value) params.search = searchQuery.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (dateFilter.value) params.date = dateFilter.value;

    const { data } = await axios.get('/api/bookings', { params });
    bookings.value = data.data;
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
      per_page: data.per_page,
      from: data.from || (bookings.value.length === 0 ? 0 : ((data.current_page - 1) * data.per_page) + 1),
      to: data.to || Math.min(data.current_page * data.per_page, data.total)
    };
  } catch (error) {
    console.error('Error fetching bookings:', error);
  } finally {
    isLoading.value = false;
  }
};

const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchBookings(1), 300);
};

const resetFilters = () => {
  searchQuery.value = '';
  statusFilter.value = '';
  dateFilter.value = '';
  fetchBookings(1);
};

const openManageModal = (booking) => {
  selectedBooking.value = booking;
  paymentAmount.value = booking.due_amount;
  newStatus.value = booking.status;
  refundAmount.value = 0;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  selectedBooking.value = null;
};

const submitPayment = async () => {
  if (!paymentAmount.value || paymentAmount.value <= 0) return;
  isSubmitting.value = true;
  try {
    await axios.put(`/api/bookings/${selectedBooking.value.id}`, {
      additional_payment: paymentAmount.value
    });
    closeModal();
    fetchBookings(pagination.value.current_page);
  } catch (error) {
    alert(error.response?.data?.message || 'Error processing payment');
  } finally {
    isSubmitting.value = false;
  }
};

const submitStatusChange = async () => {
  if (newStatus.value === selectedBooking.value.status) return;
  if (newStatus.value === 'cancelled') return; // Handled separately
  
  isSubmitting.value = true;
  try {
    await axios.put(`/api/bookings/${selectedBooking.value.id}`, {
      status: newStatus.value
    });
    closeModal();
    fetchBookings(pagination.value.current_page);
  } catch (error) {
    alert(error.response?.data?.message || 'Error updating status');
    newStatus.value = selectedBooking.value.status;
  } finally {
    isSubmitting.value = false;
  }
};

const submitCancellation = async () => {
  isSubmitting.value = true;
  try {
    await axios.put(`/api/bookings/${selectedBooking.value.id}`, {
      status: 'cancelled',
      refund_amount: refundAmount.value || 0
    });
    closeModal();
    fetchBookings(pagination.value.current_page);
  } catch (error) {
    alert(error.response?.data?.message || 'Error cancelling booking');
  } finally {
    isSubmitting.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const [year, month, day] = dateString.split('-');
  return new Date(year, month - 1, day).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTime = (timeString) => {
  return new Date('2000-01-01T' + timeString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
};

const getStatusBadgeClass = (status) => {
  const map = {
    pending: 'bg-warning text-dark',
    confirmed: 'bg-info text-dark',
    running: 'bg-primary',
    completed: 'bg-success',
    cancelled: 'bg-danger',
    no_show: 'bg-secondary'
  };
  return map[status] || 'bg-light text-dark';
};

onMounted(() => {
  fetchBookings();
});

const printInvoice = (id) => {
  window.open(`/print-invoice/${id}`, '_blank', 'width=800,height=600');
};
</script>

<style scoped>
.table > :not(caption) > * > * {
  padding: 1rem 0.5rem;
}
</style>
