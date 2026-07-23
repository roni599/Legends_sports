<template>
  <div class="print-container" v-if="booking">
    <div class="invoice-header text-center mb-4">
      <h2 class="fw-bold mb-1">LEGENDS SPORTS ARENA</h2>
      <p class="text-muted mb-0 text-sm">123 Arena Road, Sports City, BD</p>
      <p class="text-muted mb-0 text-sm">Phone: +880 1234 567890</p>
      <hr class="border-secondary mt-3 mb-3">
      <h5 class="fw-bold text-uppercase">Booking Invoice</h5>
    </div>

    <div class="row mb-4 text-sm">
      <div class="col-6">
        <p class="mb-1"><span class="fw-bold">Invoice No:</span> #INV-{{ booking.id.toString().padStart(6, '0') }}</p>
        <p class="mb-1"><span class="fw-bold">Date:</span> {{ new Date().toLocaleDateString() }}</p>
        <p class="mb-1"><span class="fw-bold">Status:</span> {{ booking.status.toUpperCase() }}</p>
      </div>
      <div class="col-6 text-end">
        <p class="mb-1"><span class="fw-bold">Client:</span> {{ booking.client.name }}</p>
        <p class="mb-1"><span class="fw-bold">Phone:</span> {{ booking.client.phone }}</p>
      </div>
    </div>

    <table class="table table-bordered table-sm border-dark">
      <thead class="table-light border-dark">
        <tr>
          <th>Description</th>
          <th class="text-end" style="width: 150px;">Amount</th>
        </tr>
      </thead>
      <tbody>
        <!-- Booking Details & Pricing Rules Merged -->
        <tr>
          <td>
            <div class="fw-bold">{{ booking.ground.name }}</div>
            <small v-for="slot in booking.slots" :key="slot.id" class="d-block">
              Date: {{ slot.date }} | Time: {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
            </small>
            <div v-if="appliedRules.length > 0" class="mt-1">
              <small v-for="(rule, index) in appliedRules" :key="'rule'+index" class="text-primary d-block">
                + {{ rule.name }}
              </small>
            </div>
          </td>
          <td class="text-end align-middle">৳ {{ booking.total_amount }}</td>
        </tr>
        
        <!-- Totals -->
        <tr>
          <td class="text-end fw-bold">Subtotal</td>
          <td class="text-end">৳ {{ booking.total_amount }}</td>
        </tr>
        <tr v-if="booking.discount > 0">
          <td class="text-end fw-bold">Discount</td>
          <td class="text-end text-danger">- ৳ {{ booking.discount }}</td>
        </tr>
        <tr>
          <td class="text-end fw-bold fs-6">Net Amount</td>
          <td class="text-end fw-bold fs-6">৳ {{ booking.total_amount - booking.discount }}</td>
        </tr>
        <tr>
          <td class="text-end fw-bold">Paid</td>
          <td class="text-end text-success">৳ {{ booking.paid_amount }}</td>
        </tr>
        <tr>
          <td class="text-end fw-bold">Total Due</td>
          <td class="text-end text-danger fw-bold fs-5">৳ {{ booking.due_amount }}</td>
        </tr>
      </tbody>
    </table>

    <div class="text-center mt-5">
      <p class="mb-1 text-sm fw-bold">Thank you for choosing Legends Sports Arena!</p>
      <p class="text-muted text-sm" style="font-size: 11px;">Rules: Non-marking shoes are strictly required. Cancellation must be made 24 hours prior.</p>
    </div>
    
    <!-- Print Buttons (Hidden during print) -->
    <div class="text-center mt-5 no-print">
      <button class="btn btn-primary px-4 me-2" @click="printPage"><i class="bi bi-printer me-2"></i>Print Invoice</button>
      <button class="btn btn-secondary px-4" @click="closeWindow">Close</button>
    </div>
  </div>
  <div v-else class="text-center mt-5">
    <div class="spinner-border text-primary"></div>
    <p class="mt-2">Loading invoice data...</p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const booking = ref(null);

const appliedRules = computed(() => {
  if (!booking.value?.applied_rules) return [];
  try {
    return JSON.parse(booking.value.applied_rules);
  } catch (e) {
    return [];
  }
});

const baseAmount = computed(() => {
  if (!booking.value) return 0;
  let totalRules = 0;
  appliedRules.value.forEach(r => totalRules += parseFloat(r.modifier || 0));
  return parseFloat(booking.value.total_amount) - totalRules;
});

const fetchBooking = async () => {
  try {
    const { data } = await axios.get(`/api/bookings/${route.params.id}`);
    booking.value = data;
    
    // Auto print when data is loaded
    setTimeout(() => {
      window.print();
    }, 500);
  } catch (error) {
    alert('Failed to load invoice data.');
  }
};

const formatTime = (timeString) => {
  return new Date('2000-01-01T' + timeString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
};

const printPage = () => window.print();
const closeWindow = () => window.close();

onMounted(() => {
  fetchBooking();
});
</script>

<style scoped>
.print-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 40px;
  background: white;
  color: #212529;
}
.print-container .text-muted {
  color: #6c757d !important;
}
.print-container .text-primary {
  color: #0d6efd !important;
}
.print-container .text-success {
  color: #198754 !important;
}
.print-container .text-danger {
  color: #dc3545 !important;
}
.print-container .table {
  color: #212529;
}

@media print {
  body * {
    visibility: hidden;
  }
  .print-container, .print-container * {
    visibility: visible;
  }
  .print-container {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 20px;
  }
  .no-print {
    display: none !important;
  }
}
</style>
