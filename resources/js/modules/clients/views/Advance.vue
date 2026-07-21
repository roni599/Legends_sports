<template>
  <div class="content-card">
    <div class="content-header">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h3 class="fs-5 m-0 text-light">Customer Advance</h3>
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

      <!-- Advance Form -->
      <div class="row mt-4">
        <div class="col-md-6">
          <div class="mb-3">
            <label class="form-label text-light">Note</label>
            <textarea v-model="form.note" class="form-control modal-input" rows="12" placeholder="Enter note (optional)"></textarea>
          </div>
        </div>
        <div class="col-md-6">
          
          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-4 col-form-label text-white fw-bold text-end pe-3">Previous Advance</label>
            <div class="col-sm-8">
              <div class="input-group">
                <span class="input-group-text bg-dark text-white border-secondary">৳</span>
                <input type="text" class="form-control modal-input text-white fw-bold" :value="previousAdvance.toFixed(2)" readonly disabled>
              </div>
            </div>
          </div>
          
          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-4 col-form-label text-white text-end pe-3">Receiving Advance</label>
            <div class="col-sm-8">
              <div class="input-group">
                <span class="input-group-text bg-dark text-white border-secondary">৳</span>
                <input type="text" class="form-control modal-input fw-bold text-white fs-5" v-model.number="form.receiving_advance">
              </div>
            </div>
          </div>

          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-4 col-form-label text-white text-end pe-3">Refund Advance</label>
            <div class="col-sm-8">
              <div class="input-group">
                <span class="input-group-text bg-dark text-white border-secondary">৳</span>
                <input type="text" class="form-control modal-input fw-bold fs-5 text-white" v-model="form.refund_advance" @input="handleRefundInput">
              </div>
            </div>
          </div>

          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-4 col-form-label text-white fw-bold text-end pe-3">Total Advance</label>
            <div class="col-sm-8">
              <div class="input-group">
                <span class="input-group-text bg-dark text-white border-secondary">৳</span>
                <input type="text" class="form-control modal-input text-warning fw-bold fs-5" :value="totalAdvance.toFixed(2)" readonly disabled>
              </div>
            </div>
          </div>
          
          <div class="row g-0 mb-3 align-items-center">
            <label class="col-sm-4 col-form-label text-white text-end pe-3">Date</label>
            <div class="col-sm-8">
              <div class="input-group">
                <span class="input-group-text bg-dark text-white border-secondary"><i class="bi bi-calendar3"></i></span>
                <div class="form-control modal-input d-flex align-items-center p-0">
                  <input type="date" class="border-0 bg-transparent text-white ps-2 w-auto" style="outline: none; box-shadow: none; color-scheme: dark;" v-model="form.date" required>
                </div>
              </div>
            </div>
          </div>
          
          <div class="row g-0 mb-4 align-items-center">
            <label class="col-sm-4 col-form-label text-white text-end pe-3">Paying With</label>
            <div class="col-sm-8">
              <div class="input-group">
                <span class="input-group-text bg-dark text-white border-secondary"><i class="bi bi-credit-card"></i></span>
                <select v-model="form.payment_method" class="form-select modal-input text-white" style="color-scheme: dark;" required>
                  <option value="cash">Cash</option>
                  <option value="bkash">Mobile Banking</option>
                  <option value="bank">Bank Payment</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-sm-4"></div>
            <div class="col-sm-8">
              <button class="btn btn-primary w-100 py-2 fw-bold" @click="submitAdvance" :disabled="submitting || (form.receiving_advance <= 0 && form.refund_advance <= 0) || form.refund_advance > previousAdvance">
                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle me-1"></i> Submit
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const clientId = route.params.id;

const client = ref(null);
const loadingClient = ref(true);
const submitting = ref(false);

const form = ref({
  note: '',
  receiving_advance: 0,
  refund_advance: 0,
  date: new Date().toISOString().split('T')[0],
  payment_method: 'cash'
});

const previousAdvance = computed(() => {
  if (!client.value) return 0;
  return client.value.total_due < 0 ? Math.abs(client.value.total_due) : 0;
});

const totalAdvance = computed(() => {
  let advance = previousAdvance.value;
  advance += Number(form.value.receiving_advance) || 0;
  advance -= Number(form.value.refund_advance) || 0;
  return advance;
});

const fetchClient = async () => {
  try {
    const res = await axios.get(`/api/clients/${clientId}`);
    client.value = res.data;
    loadingClient.value = false;
  } catch (error) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Failed to load client data', showConfirmButton: false, timer: 3000 });
    loadingClient.value = false;
  }
};

onMounted(() => {
  fetchClient();
});
const handleRefundInput = () => {
  let val = Number(form.value.refund_advance);
  if (val > previousAdvance.value) {
    form.value.refund_advance = previousAdvance.value;
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: `Maximum refund amount is ${previousAdvance.value}`, showConfirmButton: false, timer: 3000 });
  }
};

const submitAdvance = async () => {
  const receiving = parseFloat(form.value.receiving_advance) || 0;
  const refund = parseFloat(form.value.refund_advance) || 0;

  if (receiving <= 0 && refund <= 0) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Enter a receiving or refund amount greater than 0', showConfirmButton: false, timer: 3000 });
    return;
  }

  if (refund > previousAdvance.value) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Refund amount cannot exceed previous advance', showConfirmButton: false, timer: 3000 });
    return;
  }

  if (totalAdvance.value < 0) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Total advance cannot be negative', showConfirmButton: false, timer: 3000 });
    return;
  }

  submitting.value = true;
  try {
    const response = await axios.post(`/api/clients/${clientId}/advance`, form.value);
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Processed successfully!', showConfirmButton: false, timer: 2000 });
    
    // Check if receipts were created
    if (response.data.payments && response.data.payments.length > 0) {
      // For each payment created, open a print tab
      response.data.payments.forEach(paymentId => {
        window.open(`/payments/${paymentId}/print`, '_blank');
      });
    }

    router.push('/clients');
  } catch (error) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: error.response?.data?.message || 'Failed to process', showConfirmButton: false, timer: 3000 });
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.content-card {
  background-color: var(--bs-dark);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}
.content-header {
  background-color: #2b3035;
  padding: 15px 20px;
  border-bottom: 1px solid #3d4246;
}
.modal-input {
  background-color: #1a1d20;
  border: 1px solid #3d4246;
  color: #f8f9fa;
}
.modal-input:focus {
  background-color: #1a1d20;
  border-color: #0d6efd;
  color: #f8f9fa;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.modal-input:disabled {
  background-color: #2b3035;
  opacity: 1;
}
</style>
