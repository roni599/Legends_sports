<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1 text-primary fw-bold"><i class="bi bi-journal-text me-2"></i>Client Ledger</h4>
        <p class="text-muted mb-0">Detailed booking and payment history</p>
      </div>
      <router-link to="/clients" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
      </router-link>
    </div>

    <div v-if="ledgerStore.loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted">Loading ledger...</p>
    </div>

    <div v-else-if="ledgerStore.error" class="alert alert-danger">{{ ledgerStore.error }}</div>

    <template v-else-if="ledgerStore.client">
      <div class="row g-3 mb-4 align-items-stretch">
        <!-- Client Info Card -->
        <div class="col-md-5">
          <div class="card bg-dark border-secondary h-100">
            <div class="card-body p-4">
              <h6 class="text-uppercase text-white mb-3 fw-bold"><i class="bi bi-person-circle me-2"></i>Client Information</h6>
              <div class="mb-2"><span class="text-white">Name:</span> <span class="text-white fw-bold">{{ ledgerStore.client.name }}</span></div>
              <div class="mb-2"><span class="text-white">Phone:</span> <span class="text-white">{{ ledgerStore.client.phone }}</span></div>
              <div class="mb-2"><span class="text-white">Email:</span> <span class="text-white">{{ ledgerStore.client.email || '-' }}</span></div>
              <div><span class="text-white">Address:</span> <span class="text-white">{{ ledgerStore.client.address || '-' }}</span></div>
            </div>
          </div>
        </div>

        <!-- Summary Stats -->
        <div class="col-md-7">
          <div class="card bg-dark border-secondary h-100">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between mb-3">
                <span class="text-white">Booked</span>
                <span class="text-white fw-bold">{{ ledgerStore.summary.total_booked }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-white">Play</span>
                <span class="text-white fw-bold">{{ ledgerStore.summary.total_plays }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-white">Total Paid</span>
                <span class="text-white fw-bold">৳{{ ledgerStore.summary.total_paid }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-white">Total Discount</span>
                <span class="text-white fw-bold">৳{{ ledgerStore.summary.total_discount || 0 }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-white">Total Due</span>
                <span class="text-white fw-bold">৳{{ ledgerStore.summary.total_due }}</span>
              </div>
              <div class="d-flex justify-content-between mb-3">
                <span class="text-white">Total Advance</span>
                <span class="text-white fw-bold">৳{{ ledgerStore.summary.total_advance }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-white">Total Refund</span>
                <span class="text-white fw-bold">৳{{ ledgerStore.summary.total_refund || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ledger History -->
      <div class="content-card mb-4">
        <div class="p-4">
          <h5 class="text-light mb-3"><i class="bi bi-journal-text me-2"></i>Ledger History</h5>
          <div class="table-responsive">
            <table class="table table-dark table-striped table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>SL</th>
                  <th>Date</th>
                  <th>Invoice</th>
                  <th>Ground/Info</th>
                  <th>Slot</th>
                  <th>Status</th>
                  <th>Type</th>
                  <th>Method</th>
                  <th class="text-end">Amount/Price (৳)</th>
                  <th class="text-end">Due (৳)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="mergedHistory.length === 0">
                  <td colspan="10" class="text-center py-4">No ledger history found</td>
                </tr>
                <template v-for="(item, index) in mergedHistory" :key="index">
                  <!-- Booking Row -->
                  <tr v-if="item.type === 'booking'">
                    <td class="text-white">{{ index + 1 }}</td>
                    <td class="text-white">{{ formatDate(item.data.booking.slots?.[0]?.date || item.created_at) }}</td>
                    <td>
                      <a v-if="item.data.booking.invoice" :href="`/print-invoice/${item.data.booking.invoice.id}`" target="_blank" class="badge bg-primary text-decoration-none" title="Print Invoice">
                        {{ item.data.booking.invoice.invoice_number }} <i class="bi bi-printer ms-1"></i>
                      </a>
                      <span v-else class="badge bg-secondary">#INV-{{ item.data.booking.id.toString().padStart(6, '0') }}</span>
                    </td>
                    <td class="text-white">{{ item.data.booking.ground?.name || '-' }}</td>
                    <td>
                      <span v-if="item.data.booking.slots?.length > 1" class="badge bg-dark border text-light">{{ item.data.booking.slots.length }} Slots</span>
                      <span v-else-if="item.data.booking.slots?.length === 1" class="badge bg-dark border text-light">{{ formatTime(item.data.booking.slots[0].start_time) }} - {{ formatTime(item.data.booking.slots[0].end_time) }}</span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <span class="badge rounded-pill" :class="getStatusClass(item.data.booking.display_status.toLowerCase())">{{ item.data.booking.display_status }}</span>
                    </td>
                    <td><span class="badge bg-info text-dark">BOOKING</span></td>
                    <td class="text-muted">-</td>
                    <td class="text-end text-white fw-bold">৳ {{ item.data.booking.total_amount }}</td>
                    <td class="text-end fw-bold" :class="item.running_due > 0 ? 'text-danger' : (item.running_due < 0 ? 'text-success' : 'text-muted')">
                      <span v-if="item.running_due < 0">- ৳ {{ Math.abs(item.running_due).toFixed(2) }}</span>
                      <span v-else>৳ {{ item.running_due.toFixed(2) }}</span>
                    </td>
                  </tr>

                  <!-- Payment Row -->
                  <tr v-else-if="item.type === 'payment'">
                    <td class="text-white">{{ index + 1 }}</td>
                    <td class="text-white">{{ formatDate(item.data.payment.created_at) }}</td>
                    <td>
                      <a :href="`/payments/${item.data.payment.id}/print`" target="_blank" class="badge bg-primary text-decoration-none" title="Print Receipt">
                        {{ item.data.payment.transaction_id || '#' + item.data.payment.id.toString().padStart(6, '0') }} <i class="bi bi-printer ms-1"></i>
                      </a>
                    </td>
                    <td class="text-white text-capitalize">
                      <span v-if="item.data.payment.invoice?.booking?.ground?.name">
                        {{ item.data.payment.invoice.booking.ground.name }} <small v-if="item.data.payment.note" class="text-muted">({{ item.data.payment.note }})</small>
                      </span>
                      <span v-else>{{ item.data.payment.note || '-' }}</span>
                    </td>
                    <td>
                      <span v-if="item.data.payment.invoice?.booking?.slots?.length > 1" class="badge bg-dark border text-light">{{ item.data.payment.invoice.booking.slots.length }} Slots</span>
                      <span v-else-if="item.data.payment.invoice?.booking?.slots?.length === 1" class="badge bg-dark border text-light">{{ formatTime(item.data.payment.invoice.booking.slots[0].start_time) }} - {{ formatTime(item.data.payment.invoice.booking.slots[0].end_time) }}</span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <span class="badge rounded-pill" :class="getPaymentStatusClass2(item.data.payment.display_status)">
                        {{ item.data.payment.display_status }}
                      </span>
                    </td>
                    <td>
                      <span class="badge rounded-pill" :class="getPaymentTypeClass(item.data.payment.type)">
                        {{ formatPaymentType(item.data.payment.type) }}
                      </span>
                    </td>
                    <td class="text-white text-capitalize">{{ item.data.payment.payment_method }}</td>
                    <td class="text-end fw-bold" :class="['due pay', 'advance refund', 'cancelled booking', 'out', 'penalty'].includes(item.data.payment.type) ? 'text-danger' : 'text-success'">
                      ৳ {{ item.data.payment.amount }}
                    </td>
                    <td class="text-end fw-bold" :class="item.running_due > 0 ? 'text-danger' : (item.running_due < 0 ? 'text-success' : 'text-muted')">
                      <span v-if="item.running_due < 0">- ৳ {{ Math.abs(item.running_due).toFixed(2) }}</span>
                      <span v-else>৳ {{ item.running_due.toFixed(2) }}</span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useLedgerStore } from '../../../store/ledger';

const route = useRoute();
const ledgerStore = useLedgerStore();

const getRowIndex = (bIndex, sIndex) => {
  let count = 0;
  for (let i = 0; i < bIndex; i++) {
    count += ledgerStore.bookings[i].slots?.length || 0;
  }
  return count + sIndex + 1;
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';
  return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTime = (timeString) => {
  return new Date('2000-01-01T' + timeString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
};

const getStatusClass = (status) => {
  const map = {
    pending: 'bg-warning text-dark',
    confirmed: 'bg-info text-dark',
    running: 'bg-primary',
    completed: 'bg-success',
    cancelled: 'bg-danger',
    no_show: 'bg-secondary'
  };
  return map[status] || 'bg-secondary';
};

const getPaymentTypeClass = (type) => {
  const map = {
    'due receive': 'bg-success',
    'due pay': 'bg-danger',
    'due dismiss': 'bg-warning text-dark',
    'advance receive': 'bg-success',
    'advance refund': 'bg-danger',
    'book pay': 'bg-primary',
    'in': 'bg-success',
    'out': 'bg-danger',
    'cancelled booking': 'bg-danger',
    'penalty': 'bg-warning text-dark'
  };
  return map[type] || 'bg-secondary';
};

const formatPaymentType = (type) => {
  const map = {
    'due receive': 'Due Received',
    'due pay': 'Due Paid',
    'due dismiss': 'Due Dismissed',
    'advance receive': 'Advance Received',
    'advance refund': 'Advance Refunded',
    'book pay': 'Booking Payment',
    'in': 'Payment Received',
    'out': 'Invoice Refund',
    'cancelled booking': 'Cancelled Booking'
  };
  return (map[type] || type).toUpperCase();
};

const getPaymentStatusClass2 = (statusText) => {
  if (statusText === 'CANCELLED') return 'bg-danger';
  if (statusText === 'COMPLETED') return 'bg-success';
  return 'bg-info text-dark';
};

const mergedHistory = computed(() => {
  const items = [];
  
  // Add bookings (one row per booking)
  ledgerStore.bookings.forEach(booking => {
    items.push({
      type: 'booking',
      date: new Date(booking.created_at),
      created_at: booking.created_at,
      data: { booking },
      amount_value: parseFloat(booking.net_amount || booking.total_amount),
      is_addition: true,
      running_due: booking.running_due
    });
  });

  // Add payments
  ledgerStore.payments.forEach(payment => {
    const pType = payment.type;
    const amount = parseFloat(payment.amount);
    let is_addition = false;
    
    if (['due pay', 'advance refund', 'out', 'penalty'].includes(pType)) {
      is_addition = true;
    } else if (['due receive', 'advance receive', 'book pay', 'cancelled booking'].includes(pType)) {
      is_addition = false;
    }

    items.push({
      type: 'payment',
      date: new Date(payment.created_at),
      created_at: payment.created_at,
      data: { payment },
      amount_value: amount,
      is_addition: is_addition,
      running_due: payment.running_due
    });
  });

  // Sort ascending by date
  items.sort((a, b) => a.date - b.date);

  return items;
});

onMounted(() => {
  ledgerStore.fetchLedger(route.params.id);
});

onUnmounted(() => {
  ledgerStore.reset();
});
</script>
