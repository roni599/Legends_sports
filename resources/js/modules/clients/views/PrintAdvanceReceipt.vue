<template>
  <div class="print-container" v-if="payment && client">
    <div class="invoice-header text-center mb-4">
      <h2 class="fw-bold mb-1">LEGENDS SPORTS ARENA</h2>
      <p class="text-muted mb-0 text-sm">123 Arena Road, Sports City, BD</p>
      <p class="text-muted mb-0 text-sm">Phone: +880 1234 567890</p>
      <hr class="border-secondary mt-3 mb-3">
      <h5 class="fw-bold text-uppercase">{{ receiptTitle }}</h5>
    </div>

    <div class="row mb-4 text-sm">
      <div class="col-6">
        <p class="mb-1"><span class="fw-bold">Receipt No:</span> {{ payment.transaction_id || '#' + payment.id.toString().padStart(6, '0') }}</p>
        <p class="mb-1"><span class="fw-bold">Date:</span> {{ formatDate(payment.created_at) }}</p>
        <p class="mb-1 text-capitalize"><span class="fw-bold">Type:</span> {{ payment.type }}</p>
      </div>
      <div class="col-6 text-end">
        <p class="mb-1"><span class="fw-bold">Client:</span> {{ client.name }}</p>
        <p class="mb-1"><span class="fw-bold">Phone:</span> {{ client.phone }}</p>
      </div>
    </div>

    <!-- Summary Details -->
    <div class="mb-4">
      <table class="table table-borderless table-sm w-100" style="max-width: 400px;">
        <tbody>
          <tr>
            <td class="fw-bold ps-0">{{ amountLabel }}:</td>
            <td class="text-end fw-bold">{{ parseFloat(payment.amount).toFixed(2) }}</td>
          </tr>
          <tr v-if="['advance receive', 'advance refund'].includes(payment.type)">
            <td class="fw-bold ps-0">Previous Balance:</td>
            <td class="text-end">{{ parseFloat(totalAdvanced).toFixed(2) }}</td>
          </tr>
          <tr v-if="['advance receive', 'advance refund'].includes(payment.type)" class="border-top">
            <td class="fw-bold ps-0">Current Balance:</td>
            <td class="text-end fw-bold">{{ parseFloat(remainingAdvance).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>
      <p class="mt-2 text-sm"><span class="fw-bold">In Words:</span> {{ numberToWords(parseFloat(payment.amount)) }} TK Only</p>
    </div>

    <h6 class="fw-bold mb-2">Payment Details</h6>
    <table class="table table-bordered table-sm border-dark text-sm">
      <thead class="table-light border-dark">
        <tr>
          <th>Sl</th>
          <th>Date</th>
          <th>Payment Method</th>
          <th>Payment By</th>
          <th class="text-end">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td>{{ formatDate(payment.created_at) }}</td>
          <td class="text-capitalize">{{ payment.payment_method }}</td>
          <td>-</td>
          <td class="text-end">TK {{ parseFloat(payment.amount).toFixed(2) }}</td>
        </tr>
      </tbody>
    </table>

    <div class="mt-5 pt-5 row text-center">
      <div class="col-6">
        <div class="border-top border-dark mx-4 pt-2 fw-bold text-sm">Customer Signature</div>
      </div>
      <div class="col-6">
        <div class="border-top border-dark mx-4 pt-2 fw-bold text-sm">Authorized Signature</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { ToWords } from 'to-words';

const route = useRoute();
const payment = ref(null);
const client = ref(null);

const toWords = new ToWords({
  localeCode: 'en-IN',
  converterOptions: { currency: false, ignoreDecimal: false, ignoreZeroCurrency: false }
});

const isRefund = computed(() => {
  return payment.value && payment.value.type === 'advance refund';
});

const receiptTitle = computed(() => {
  if (!payment.value) return 'Receipt';
  const type = payment.value.type;
  if (type === 'cancelled booking') return 'Cancellation Receipt';
  if (type === 'out' || type === 'advance refund') return 'Refund Receipt';
  if (type === 'penalty') return 'Penalty Receipt';
  if (type === 'due dismiss') return 'Dismissal Receipt';
  return 'Payment Receipt';
});

const amountLabel = computed(() => {
  if (!payment.value) return 'Amount';
  const type = payment.value.type;
  if (type === 'cancelled booking') return 'Cancelled Amount';
  if (type === 'out' || type === 'advance refund') return 'Refund Amount';
  if (type === 'penalty') return 'Penalty Amount';
  if (type === 'due dismiss') return 'Dismissed Amount';
  return 'Paid Amount';
});

// Calculate total advanced and remaining based on current total_due.
// If total_due is negative, it's an advance.
const remainingAdvance = computed(() => {
  if (!client.value) return 0;
  return client.value.total_due < 0 ? Math.abs(client.value.total_due) : 0;
});

const totalAdvanced = computed(() => {
  if (!payment.value) return 0;
  // If we just refunded, the advance before this was remaining + refunded amount.
  // If we just received, the advance before this was remaining - received amount.
  if (isRefund.value) {
    return remainingAdvance.value + parseFloat(payment.value.amount);
  } else {
    // If received, the 'Total Advanced' after this receipt is exactly the remaining advance.
    return remainingAdvance.value;
  }
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-CA');
};

const numberToWords = (num) => {
  try {
    return toWords.convert(num);
  } catch (e) {
    return num.toString();
  }
};

onMounted(async () => {
  try {
    const res = await axios.get(`/api/payments/${route.params.id}`);
    payment.value = res.data.payment;
    client.value = res.data.client;
    
    // Auto print after a small delay for rendering
    setTimeout(() => {
      window.print();
    }, 500);
  } catch (error) {
    console.error('Error fetching payment for print', error);
  }
});
</script>

<style scoped>
.print-container {
  padding: 30px;
  max-width: 800px;
  margin: 0 auto;
  background: white;
  color: black;
  font-family: Arial, sans-serif;
}
.text-sm {
  font-size: 0.9rem;
}
@media print {
  body * {
    visibility: hidden;
  }
  #app, #app * {
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
    padding: 0;
  }
  @page {
    margin: 0.5cm;
  }
}
</style>
