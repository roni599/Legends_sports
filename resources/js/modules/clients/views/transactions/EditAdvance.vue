<template>
  <div class="content-card">
    <div class="content-header">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h3 class="fs-5 m-0 text-light">Edit Advance</h3>
        <router-link to="/clients/transactions/advance" class="btn btn-outline-light btn-sm ms-auto">
          <i class="bi bi-arrow-left me-1"></i>Back to List
        </router-link>
      </div>
    </div>
    
    <div class="p-4" v-if="loading">
      <div class="text-center text-light">Loading transaction details...</div>
    </div>
    
    <div class="p-4" v-else-if="transaction && client">
      <div class="mb-4 text-light">
        <div class="mb-1"><span class="fw-bold me-2">Name:</span>{{ client.name }}</div>
        <div class="mb-1"><span class="fw-bold me-2">Mobile:</span>{{ client.phone || 'N/A' }}</div>
        <div class="mb-1"><span class="fw-bold me-2">Email:</span>{{ client.email || 'N/A' }}</div>
      </div>

      <div class="table-responsive mb-4">
        <table class="table table-dark table-striped table-hover table-bordered align-middle">
          <thead style="background-color: #444;">
            <tr>
              <th style="width: 1%; white-space: nowrap;" class="pe-2 text-center">
                <input type="checkbox" class="form-check-input" checked disabled>
              </th>
              <th class="ps-0">Invoice</th>
              <th>Invoice Type</th>
              <th>Date</th>
              <th>Invoice Amount (৳)</th>
              <th style="width: 250px;">Paying Amount (৳)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!invoice">
              <td colspan="6" class="text-center py-4">No specific invoice found for this transaction.</td>
            </tr>
            <tr v-else>
              <td class="pe-2 text-center">
                <input type="checkbox" class="form-check-input" checked disabled>
              </td>
              <td class="ps-0">{{ invoice.invoice_number }}</td>
              <td class="text-capitalize">{{ invoice.type }}</td>
              <td>{{ invoice.date }}</td>
              <td>{{ invoice.grand_total }}</td>
              <td>
                <input type="number" 
                       class="form-control form-control-sm modal-input fw-bold" 
                       :class="transaction.type === 'advance receive' ? 'text-success' : 'text-danger'"
                       v-model.number="paymentForm.amount" 
                       @input="handleAmountInput">
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="row mt-4">
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label text-light">Note</label>
            <textarea v-model="paymentForm.note" class="form-control modal-input custom-note-input" rows="4" placeholder="Enter payment note (optional)"></textarea>
          </div>
        </div>
        <div class="col-md-6">
          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-3 col-form-label text-light text-end pe-2">Paying Amount</label>
            <div class="col-sm-9">
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary">৳</span>
                <input type="number" class="form-control modal-input fw-bold fs-5" :class="transaction.type === 'advance receive' ? 'text-success' : 'text-danger'" v-model.number="paymentForm.amount" @input="handleAmountInput">
              </div>
            </div>
          </div>
          
          <div class="row g-0 mb-4 align-items-center">
            <label class="col-sm-3 col-form-label text-light text-end pe-2">Payment Method</label>
            <div class="col-sm-9">
              <div class="input-group">
                <span class="input-group-text bg-dark text-light border-secondary"><i class="bi bi-credit-card"></i></span>
                <select v-model="paymentForm.payment_method" class="form-select modal-input" disabled>
                  <option value="cash">Cash</option>
                  <option value="bkash">Mobile Banking</option>
                  <option value="bank">Bank Payment</option>
                </select>
              </div>
              <small class="text-muted d-block mt-1">Payment method cannot be changed.</small>
            </div>
          </div>
          
          <div class="row">
            <div class="col-sm-3"></div>
            <div class="col-sm-9">
              <button class="btn btn-primary w-100 py-2 fw-bold" @click="submitUpdate" :disabled="submitting || paymentForm.amount < 0">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle me-1"></i> Confirm Update
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const txId = route.params.id;

const loading = ref(true);
const submitting = ref(false);

const transaction = ref(null);
const client = ref(null);
const invoice = ref(null);

const paymentForm = ref({
  amount: 0,
  note: '',
  payment_method: 'cash'
});

const fetchTransaction = async () => {
  try {
    const res = await axios.get(`/api/client-transactions/${txId}`);
    transaction.value = res.data;
    client.value = res.data.client;
    invoice.value = res.data.invoice;
    
    paymentForm.value.amount = Number(res.data.amount);
    paymentForm.value.payment_method = res.data.payment_method;
    
    loading.value = false;
  } catch (error) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Failed to load transaction details', showConfirmButton: false, timer: 3000 });
    router.push('/clients/transactions/advance');
  }
};

onMounted(() => {
  fetchTransaction();
});

const handleAmountInput = () => {
  let val = Number(paymentForm.value.amount) || 0;
  
  if (val < 0) {
    val = 0;
  }
  
  if (invoice.value && val > Number(invoice.value.grand_total)) {
    val = Number(invoice.value.grand_total);
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: `Maximum amount cannot exceed invoice total (${val})`, showConfirmButton: false, timer: 3000 });
  }
  
  paymentForm.value.amount = val;
};

const submitUpdate = async () => {
  if (paymentForm.value.amount < 0) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Please enter a valid amount (0 or more)', showConfirmButton: false, timer: 3000 });
    return;
  }

  submitting.value = true;
  try {
    await axios.put(`/api/client-transactions/${txId}`, {
      amount: paymentForm.value.amount
    });
    
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Transaction updated successfully!', showConfirmButton: false, timer: 2000 });
    router.push('/clients/transactions/advance');
  } catch (error) {
    const msg = error.response?.data?.message || 'Update failed';
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: msg, showConfirmButton: false, timer: 3000 });
    submitting.value = false;
  }
};
</script>

<style scoped>
.modal-input { background-color: #2a2a2a !important; color: #fff !important; border-color: #444 !important; color-scheme: dark; }
.modal-input:focus { background-color: #333 !important; color: #fff !important; border-color: #666 !important; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); }
.modal-input:disabled, .modal-input[readonly] { background-color: #222 !important; color: #888 !important; }
select.modal-input { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); }
.custom-note-input::placeholder { color: #ffffff !important; opacity: 1; }
.custom-note-input { color: #ffffff !important; }
</style>
