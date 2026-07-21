<template>
  <div class="content-card">
    <div class="content-header">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h3 class="fs-5 m-0 text-light">Receive Due Payment</h3>
        <router-link to="/clients" class="btn btn-outline-light btn-sm ms-auto">
          <i class="bi bi-arrow-left me-1"></i>Back to Clients
        </router-link>
      </div>
    </div>
    
    <div class="p-4" v-if="loadingClient">
      <div class="text-center text-light">Loading client details...</div>
    </div>
    
    <div class="p-4" v-else-if="client">
      <!-- Client Details -->
      <div class="mb-4 text-light">
        <div class="mb-1"><span class="fw-bold me-2">Name:</span>{{ client.name }}</div>
        <div class="mb-1"><span class="fw-bold me-2">Mobile:</span>{{ client.phone || 'N/A' }}</div>
        <div class="mb-1"><span class="fw-bold me-2">Email:</span>{{ client.email || 'N/A' }}</div>
      </div>

      <!-- Invoices Table -->
      <div class="table-responsive mb-4">
        <table class="table table-dark table-striped table-hover table-bordered align-middle">
          <thead style="background-color: #444;">
            <tr>
              <th style="width: 1%; white-space: nowrap;" class="pe-2 text-center">
                <input type="checkbox" class="form-check-input" v-model="selectAll" @change="toggleAll">
              </th>
              <th class="ps-0">Invoice</th>
              <th>Invoice Amount (৳)</th>
              <th>Due Amount (৳)</th>
              <th style="width: 250px;">Paying Amount (৳)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loadingInvoices">
              <td colspan="5" class="text-center py-4">Loading invoices...</td>
            </tr>
            <tr v-else-if="invoices.length === 0">
              <td colspan="5" class="text-center py-4">No due invoices found for this client.</td>
            </tr>
            <tr v-else v-for="invoice in invoices" :key="invoice.id">
              <td class="pe-2 text-center">
                <input type="checkbox" class="form-check-input" v-model="invoice.selected" @change="updateTotals">
              </td>
              <td class="ps-0">{{ invoice.invoice_number }}</td>
              <td>{{ invoice.grand_total }}</td>
              <td class="text-danger fw-bold">{{ invoice.due }}</td>
              <td>
                <input type="text" 
                       class="form-control form-control-sm modal-input fw-bold text-success" 
                       v-model.number="invoice.paying_amount" 
                       :disabled="!invoice.selected"
                       @input="updateTotals">
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payment Form -->
      <div class="row mt-4">
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label text-light">Note</label>
            <textarea v-model="paymentForm.note" class="form-control modal-input" rows="7" placeholder="Enter payment note (optional)"></textarea>
          </div>
        </div>
        <div class="col-md-6">
          <!-- Right side: horizontal layout without card, g-0 removes gap -->
          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-3 col-form-label text-light fw-bold text-end pe-2">Total Payable</label>
            <div class="col-sm-9">
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary">৳</span>
                <input type="text" class="form-control modal-input text-danger fw-bold fs-5" :value="totalPayable.toFixed(2)" readonly>
              </div>
            </div>
          </div>
          
          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-3 col-form-label text-light text-end pe-2">Paying Amount</label>
            <div class="col-sm-9">
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary">৳</span>
                <input type="text" class="form-control modal-input fw-bold text-success fs-5" v-model="paymentForm.amount" @input="handleAmountInput">
              </div>
            </div>
          </div>
          
          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-3 col-form-label text-light text-end pe-2">Paying Date</label>
            <div class="col-sm-9">
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary"><i class="bi bi-calendar3"></i></span>
                <div class="form-control modal-input d-flex align-items-center p-0">
                  <input type="date" class="border-0 bg-transparent text-white ps-2 w-auto" style="outline: none; box-shadow: none;" v-model="paymentForm.date" required>
                </div>
              </div>
            </div>
          </div>
          
          <div class="row g-0 mb-4 align-items-center">
            <label class="col-sm-3 col-form-label text-light text-end pe-2">Paying With</label>
            <div class="col-sm-9">
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary"><i class="bi bi-credit-card"></i></span>
                <select v-model="paymentForm.payment_method" class="form-select modal-input" required>
                  <option value="cash">Cash</option>
                  <option value="bkash">Mobile Banking</option>
                  <option value="bank">Bank Payment</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-sm-3"></div>
            <div class="col-sm-9">
              <button class="btn btn-primary w-100 py-2 fw-bold" @click="submitPayment" :disabled="submitting || paymentForm.amount <= 0">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle me-1"></i> Confirm Receive
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<!-- Trigger Vite HMR -->
<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const clientId = route.params.id;

const client = ref(null);
const invoices = ref([]);
const loadingClient = ref(true);
const loadingInvoices = ref(true);
const submitting = ref(false);
const selectAll = ref(false);

const paymentForm = ref({
  amount: 0,
  note: '',
  date: new Date().toISOString().split('T')[0],
  payment_method: 'cash'
});

const totalPayable = computed(() => {
  return invoices.value
    .filter(inv => inv.selected)
    .reduce((sum, inv) => sum + Number(inv.due), 0);
});

const grandTotalDue = computed(() => {
  return invoices.value.reduce((sum, inv) => sum + Number(inv.due), 0);
});

const fetchClientAndInvoices = async () => {
  try {
    const clientRes = await axios.get(`/api/clients/${clientId}`);
    client.value = clientRes.data;
    loadingClient.value = false;
    
    const invRes = await axios.get(`/api/clients/${clientId}/due-invoices`);
    invoices.value = invRes.data.invoices.map(inv => ({
      ...inv,
      selected: false,
      paying_amount: inv.due
    }));
    loadingInvoices.value = false;
  } catch (error) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Failed to load data', showConfirmButton: false, timer: 3000 });
    loadingClient.value = false;
    loadingInvoices.value = false;
  }
};

onMounted(() => {
  fetchClientAndInvoices();
});

const toggleAll = () => {
  invoices.value.forEach(inv => {
    inv.selected = selectAll.value;
  });
  updateTotals();
};

watch(() => invoices.value.map(i => i.selected), () => {
  const allSelected = invoices.value.length > 0 && invoices.value.every(inv => inv.selected);
  const someSelected = invoices.value.some(inv => inv.selected);
  selectAll.value = allSelected;
  
  if (!allSelected && selectAll.value) {
    selectAll.value = false;
  }
});

const handleAmountInput = () => {
  let val = Number(paymentForm.value.amount);
  if (val > grandTotalDue.value) {
    paymentForm.value.amount = grandTotalDue.value;
    selectAll.value = true;
    toggleAll(); // this will select all and updateTotals to grandTotalDue
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: `Maximum payable amount is ${grandTotalDue.value}`, showConfirmButton: false, timer: 3000 });
  }
};

const updateTotals = () => {
  const amount = invoices.value
    .filter(inv => inv.selected)
    .reduce((sum, inv) => sum + (Number(inv.paying_amount) || 0), 0);
  if (amount > 0) {
    paymentForm.value.amount = amount;
  }
};

const submitPayment = async () => {
  const selectedInvoices = invoices.value
    .filter(inv => inv.selected && Number(inv.paying_amount) > 0)
    .map(inv => ({
      id: inv.id,
      amount: Number(inv.paying_amount)
    }));

  if (paymentForm.value.amount <= 0) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please enter a valid amount', showConfirmButton: false, timer: 3000 });
    return;
  }

  submitting.value = true;
  try {
    await axios.post(`/api/clients/${clientId}/receive-invoices`, {
      payment_method: paymentForm.value.payment_method,
      note: paymentForm.value.note,
      date: paymentForm.value.date,
      amount: paymentForm.value.amount,
      invoices: selectedInvoices.length > 0 ? selectedInvoices : null
    });
    
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Payment successful!', showConfirmButton: false, timer: 2000 });
    router.push('/clients');
  } catch (error) {
    const msg = error.response?.data?.message || 'Payment failed';
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: msg, showConfirmButton: false, timer: 3000 });
    submitting.value = false;
  }
};
</script>

<style scoped>
.modal-input {
  background-color: #2a2a2a !important;
  color: #fff !important;
  border-color: #444 !important;
  color-scheme: dark;
}
.modal-input:focus {
  background-color: #333 !important;
  color: #fff !important;
  border-color: #666 !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
.modal-input:disabled, .modal-input[readonly] {
  background-color: #222 !important;
  color: #888 !important;
}
input[type="date"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
}
select.modal-input {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
}
</style>
