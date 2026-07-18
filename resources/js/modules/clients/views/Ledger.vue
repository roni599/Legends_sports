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
                <span class="text-white">Total Due</span>
                <span class="text-white fw-bold">৳{{ ledgerStore.summary.total_due }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-white">Total Advance</span>
                <span class="text-white fw-bold">৳{{ ledgerStore.summary.total_advance }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ledger Table -->
      <div class="content-card">
        <div class="p-4">
          <div class="table-responsive">
            <table class="table table-dark table-striped table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>SL</th>
                  <th>Date</th>
                  <th>Invoice</th>
                  <th>Ground</th>
                  <th>Slot</th>
                  <th>Status</th>
                  <th class="text-center">Booked</th>
                  <th class="text-center">Play</th>
                  <th class="text-end">Amount (৳)</th>
                  <th class="text-end">Due (৳)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="ledgerStore.bookings.length === 0">
                  <td colspan="10" class="text-center py-4">No booking history found</td>
                </tr>
                <template v-for="(booking, bIndex) in ledgerStore.bookings" :key="booking.id">
                  <tr v-for="(slot, sIndex) in booking.slots" :key="slot.id">
                    <td class="text-white">{{ getRowIndex(bIndex, sIndex) }}</td>
                    <td class="text-white">{{ formatDate(slot.date) }}</td>
                    <td><span class="badge bg-secondary">#INV-{{ booking.id.toString().padStart(6, '0') }}</span></td>
                    <td class="text-white">{{ booking.ground?.name }}</td>
                    <td>
                      <span class="badge bg-dark border text-light">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</span>
                    </td>
                    <td>
                      <span class="badge rounded-pill" :class="getStatusClass(booking.status)">{{ booking.status.toUpperCase() }}</span>
                    </td>
                    <td class="text-center text-white fw-bold">1</td>
                    <td class="text-center text-white fw-bold">{{ booking.status === 'completed' ? 1 : 0 }}</td>
                    <td class="text-end text-white fw-bold">৳ {{ slot.price }}</td>
                    <td class="text-end fw-bold" :class="getSlotDue(booking, slot) > 0 ? 'text-danger' : 'text-success'">
                      ৳ {{ getSlotDue(booking, slot) }}
                    </td>
                  </tr>
                </template>
              </tbody>
              <tfoot v-if="ledgerStore.bookings.length > 0">
                <tr class="border-top border-secondary">
                  <td colspan="6" class="text-end fw-bold text-light">Totals</td>
                  <td class="text-center fw-bold text-white fs-6">{{ ledgerStore.summary.total_booked }}</td>
                  <td class="text-center fw-bold text-white fs-6">{{ ledgerStore.summary.total_plays }}</td>
                  <td class="text-end fw-bold text-white fs-6">৳ {{ ledgerStore.summary.total_booked_amount }}</td>
                  <td class="text-end fw-bold" :class="ledgerStore.summary.total_due > 0 ? 'text-danger' : 'text-success'">৳ {{ ledgerStore.summary.total_due }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
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
  const [year, month, day] = dateString.split('-');
  return new Date(year, month - 1, day).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
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

const getSlotDue = (booking, slot) => {
  if (booking.status === 'cancelled') return 0;
  const totalAmount = parseFloat(booking.total_amount || 0);
  const paidAmount = parseFloat(booking.paid_amount || 0);
  const due = parseFloat(booking.due_amount || 0);
  if (due <= 0) return 0;
  const slotCount = booking.slots?.length || 1;
  const slotShare = totalAmount > 0 ? (slot.price / totalAmount) : (1 / slotCount);
  return Math.round(due * slotShare);
};

onMounted(() => {
  ledgerStore.fetchLedger(route.params.id);
});

onUnmounted(() => {
  ledgerStore.reset();
});
</script>
