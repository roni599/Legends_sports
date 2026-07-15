<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Client List</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search name or phone..." style="width: 200px;">
        <router-link to="/clients/create" class="btn btn-primary btn-sm">
          + Add New Client
        </router-link>
      </div>
    </div>
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Total Due (৳)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clientStore.loading">
              <td colspan="6" class="text-center py-4">Loading clients...</td>
            </tr>
            <tr v-else-if="clientStore.clients.length === 0">
              <td colspan="6" class="text-center py-4">No clients found</td>
            </tr>
            <tr v-else v-for="client in clientStore.clients" :key="client.id">
              <td>#{{ client.id }}</td>
              <td>{{ client.name }}</td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  {{ client.phone }}
                  <a :href="'https://wa.me/' + client.phone" target="_blank" class="btn btn-sm btn-success p-1 lh-1" title="WhatsApp">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                  </a>
                  <a :href="'tel:' + client.phone" class="btn btn-sm btn-primary p-1 lh-1" title="Call">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/></svg>
                  </a>
                </div>
              </td>
              <td>{{ client.email || '-' }}</td>
              <td :class="{'text-danger fw-bold': client.total_due > 0}">{{ client.total_due }}</td>
              <td>
                <button @click="openLedger(client)" class="btn btn-sm btn-warning me-2 text-dark fw-bold">Ledger</button>
                <router-link :to="`/clients/${client.id}/edit`" class="btn btn-sm btn-outline-info me-2">Edit</router-link>
                <button @click="clientStore.deleteClient(client.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination Design per user requirement -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-secondary small">Showing {{ clientStore.clients.length }} entries of {{ clientStore.total }} entries</span>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="clientStore.page === 1" @click="changePage(clientStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="clientStore.page * 10 >= clientStore.total" @click="changePage(clientStore.page + 1)">Next</button>
        </div>
      </div>
    </div>

    <!-- Ledger Modal -->
    <div class="modal fade" id="ledgerModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Financial Ledger: {{ selectedClient?.name }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div class="p-3 bg-secondary bg-opacity-25 border-bottom border-secondary d-flex justify-content-between align-items-center">
              <div><strong>Total Bookings:</strong> {{ clientLedger.length }}</div>
              <div class="d-flex align-items-center gap-3">
                <span class="fs-5"><strong class="text-danger">Total Due:</strong> ৳ {{ selectedClient?.total_due }}</span>
                <button v-if="selectedClient?.total_due > 0" @click="showPaymentForm = !showPaymentForm" class="btn btn-sm btn-success fw-bold">
                  {{ showPaymentForm ? 'Cancel Payment' : 'Receive Due' }}
                </button>
              </div>
            </div>
            
            <!-- Receive Payment Form -->
            <div v-if="showPaymentForm" class="p-3 bg-light text-dark border-bottom border-secondary">
              <h6 class="fw-bold mb-3">Receive Due Payment</h6>
              <form @submit.prevent="submitDuePayment" class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label text-sm fw-bold">Amount (৳)</label>
                  <input type="number" v-model.number="paymentForm.amount" class="form-control form-control-sm" :max="selectedClient?.total_due" min="1" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-sm fw-bold">Payment Method</label>
                  <select v-model="paymentForm.payment_method" class="form-select form-select-sm" required>
                    <option value="cash">Cash</option>
                    <option value="bkash">bKash</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="card">Card</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <button type="submit" class="btn btn-success btn-sm w-100 fw-bold" :disabled="isSubmittingPayment">
                    <span v-if="isSubmittingPayment" class="spinner-border spinner-border-sm me-1"></span>
                    Confirm Payment
                  </button>
                </div>
              </form>
            </div>
            
            <!-- Tabs for Ledger / Payments -->
            <ul class="nav nav-tabs nav-fill bg-dark border-bottom-0" id="ledgerTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active text-light bg-dark border-0 rounded-0 border-bottom border-primary" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">Booking History</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link text-light bg-dark border-0 rounded-0" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">Due Collections</button>
              </li>
            </ul>

            <div class="tab-content" id="ledgerTabsContent">
              <!-- Bookings Tab -->
              <div class="tab-pane fade show active" id="bookings" role="tabpanel">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                  <table class="table table-dark table-striped table-hover mb-0 text-sm">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Ground</th>
                        <th>Status</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="isLoadingLedger">
                        <td colspan="6" class="text-center py-4">Loading bookings...</td>
                      </tr>
                      <tr v-else-if="clientLedger.length === 0">
                        <td colspan="6" class="text-center py-4">No booking history found</td>
                      </tr>
                      <tr v-else v-for="booking in clientLedger" :key="booking.id">
                        <td>{{ new Date(booking.created_at).toLocaleDateString() }}</td>
                        <td>{{ booking.ground.name }}</td>
                        <td>
                          <span class="badge" :class="booking.status === 'completed' ? 'bg-success' : (booking.status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark')">
                            {{ booking.status }}
                          </span>
                        </td>
                        <td class="text-end">৳ {{ booking.total_amount }}</td>
                        <td class="text-end text-success">৳ {{ booking.paid_amount }}</td>
                        <td class="text-end" :class="{'text-danger fw-bold': booking.due_amount > 0}">৳ {{ booking.due_amount }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Payments Tab -->
              <div class="tab-pane fade" id="payments" role="tabpanel">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                  <table class="table table-dark table-striped table-hover mb-0 text-sm">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Transaction ID</th>
                        <th>Method</th>
                        <th class="text-end text-success">Amount Received</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="isLoadingLedger">
                        <td colspan="4" class="text-center py-4">Loading payments...</td>
                      </tr>
                      <tr v-else-if="clientPayments.length === 0">
                        <td colspan="4" class="text-center py-4">No due collections found</td>
                      </tr>
                      <tr v-else v-for="payment in clientPayments" :key="payment.id">
                        <td>{{ new Date(payment.created_at).toLocaleString() }}</td>
                        <td>{{ payment.transaction_id }}</td>
                        <td class="text-uppercase">{{ payment.payment_method }}</td>
                        <td class="text-end fw-bold text-success">৳ {{ payment.amount }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useClientStore } from '../../../store/clients';

const clientStore = useClientStore();
const searchQuery = ref('');
let searchTimeout = null;

onMounted(() => {
  clientStore.fetchClients();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    clientStore.fetchClients(1, searchQuery.value);
  }, 300);
};

const changePage = (page) => {
  clientStore.fetchClients(page, searchQuery.value);
};

// Ledger Logic
import axios from 'axios';
const selectedClient = ref(null);
const clientLedger = ref([]);
const clientPayments = ref([]);
const isLoadingLedger = ref(false);
let bsLedgerModal = null;

// Payment Form Logic
const showPaymentForm = ref(false);
const isSubmittingPayment = ref(false);
const paymentForm = ref({
  amount: 0,
  payment_method: 'cash'
});

onMounted(() => {
  bsLedgerModal = new bootstrap.Modal(document.getElementById('ledgerModal'));
});

const openLedger = async (client) => {
  selectedClient.value = client;
  clientLedger.value = [];
  clientPayments.value = [];
  showPaymentForm.value = false;
  paymentForm.value.amount = client.total_due;
  bsLedgerModal.show();
  isLoadingLedger.value = true;
  
  try {
    const response = await axios.get(`/api/clients/${client.id}/ledger`);
    clientLedger.value = response.data.ledger;
    clientPayments.value = response.data.payments;
  } catch (error) {
    alert('Failed to load ledger data.');
  } finally {
    isLoadingLedger.value = false;
  }
};

const submitDuePayment = async () => {
  if (!selectedClient.value) return;
  isSubmittingPayment.value = true;
  try {
    const response = await axios.post(`/api/clients/${selectedClient.value.id}/receive-payment`, paymentForm.value);
    selectedClient.value = response.data.client;
    
    // Update main client list visually
    const idx = clientStore.clients.findIndex(c => c.id === selectedClient.value.id);
    if (idx !== -1) {
      clientStore.clients[idx].total_due = selectedClient.value.total_due;
    }
    
    // Refresh ledger arrays
    const ledgerRes = await axios.get(`/api/clients/${selectedClient.value.id}/ledger`);
    clientLedger.value = ledgerRes.data.ledger;
    clientPayments.value = ledgerRes.data.payments;
    
    showPaymentForm.value = false;
    alert('Payment received successfully!');
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to process payment.');
  } finally {
    isSubmittingPayment.value = false;
  }
};
</script>
