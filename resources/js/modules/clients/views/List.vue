<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Client List</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search name or phone..." style="width: 200px;">
        <button class="btn btn-primary btn-sm" @click="openModal">
          + Add New Client
        </button>
      </div>
    </div>
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>SL</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Address</th>
              <th class="text-center">Play Time</th>
              <th class="text-end">Advance (৳)</th>
              <th class="text-end">Refund (৳)</th>
              <th class="text-end">Total Billed</th>
              <th class="text-end">Total Paid</th>
              <th class="text-end">Due (৳)</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clientStore.loading">
              <td colspan="12" class="text-center py-4">Loading clients...</td>
            </tr>
            <tr v-else-if="clientStore.clients.length === 0">
              <td colspan="12" class="text-center py-4">No clients found</td>
            </tr>
            <tr v-else v-for="(client, index) in clientStore.clients" :key="client.id">
              <td class="text-white">{{ (clientStore.page - 1) * 10 + index + 1 }}</td>
              <td class="fw-bold text-white">{{ client.name }}</td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="text-white">{{ client.phone }}</span>
                  <a :href="'https://wa.me/' + (client.phone.startsWith('0') ? '88' + client.phone : client.phone).replace(/[^0-9]/g, '')" target="_blank" class="btn btn-sm btn-success p-1 lh-1" title="WhatsApp">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                  </a>
                  <a :href="'tel:' + client.phone" class="text-primary ms-1" title="Call">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/></svg>
                  </a>
                </div>
              </td>
              <td class="small text-light">{{ client.email || '-' }}</td>
              <td class="small text-light">{{ client.address || '-' }}</td>
              <td class="text-center text-white">{{ client.play_time || 0 }}</td>
              <td class="text-end text-white">{{ client.advance_amount || 0 }}</td>
              <td class="text-end text-white">{{ client.refund_amount || 0 }}</td>
              <td class="text-end text-white">{{ client.total_billed || 0 }}</td>
              <td class="text-end text-white">{{ client.total_paid || 0 }}</td>
              <td class="text-end text-white">{{ client.due_amount || 0 }}</td>
              <td class="text-end">
                <span v-if="client.status === 'deactive'" class="badge bg-danger me-1">Deactive</span>
                <div class="dropdown" @click.stop>
                  <button class="btn btn-sm btn-outline-light border-0 px-2 py-0" @click="toggleDropdown(client.id, $event)">
                    <i class="bi bi-three-dots-vertical fs-5"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3" v-if="clientStore.total > 0">
        <span class="text-light small">
          Showing {{ ((clientStore.page - 1) * 10) + 1 }} to {{ Math.min(clientStore.page * 10, clientStore.total) }} of {{ clientStore.total }} entries
        </span>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="clientStore.page === 1" @click="changePage(clientStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="clientStore.page * 10 >= clientStore.total" @click="changePage(clientStore.page + 1)">Next</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 3-Dot Dropdown Menu -->
  <Teleport to="body">
    <div v-if="openDropdown" class="dropdown-backdrop" @click="openDropdown = null"></div>
    <ul v-if="openDropdown" class="custom-dropdown-menu" :style="dropdownPos">
      <li><router-link class="dropdown-item" :to="`/clients/${dropdownClient.id}/edit`" @click="openDropdown = null"><i class="bi bi-pencil-square me-2"></i>Edit</router-link></li>
      <li><hr class="dropdown-divider"></li>
      <li><router-link class="dropdown-item" :to="`/clients/${dropdownClient.id}/ledger`" @click="openDropdown = null"><i class="bi bi-journal-text me-2"></i>Ledger</router-link></li>
      <li><router-link class="dropdown-item" :to="`/clients/${dropdownClient.id}/pay`" @click="openDropdown = null"><i class="bi bi-wallet2 me-2"></i>Pay</router-link></li>
      <li><router-link class="dropdown-item" :to="`/clients/${dropdownClient.id}/receive`" @click="openDropdown = null"><i class="bi bi-box-arrow-in-down me-2"></i>Receive</router-link></li>
      <li><router-link class="dropdown-item" :to="`/clients/${dropdownClient.id}/dismiss`" @click="openDropdown = null"><i class="bi bi-x-circle me-2"></i>Dismiss</router-link></li>
      <li><router-link class="dropdown-item" :to="`/clients/${dropdownClient.id}/advance`" @click="openDropdown = null"><i class="bi bi-forward me-2"></i>Advance</router-link></li>
      <li><a class="dropdown-item" href="#" @click.prevent="handleToggleStatus">
        <i :class="dropdownClient.status === 'active' ? 'bi bi-person-dash me-2' : 'bi bi-person-check me-2'"></i>{{ dropdownClient.status === 'active' ? 'Deactive' : 'Active' }}
      </a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item text-danger" href="#" @click.prevent="clientStore.deleteClient(dropdownClient.id); openDropdown = null"><i class="bi bi-trash me-2"></i>Delete</a></li>
    </ul>
  </Teleport>

  <!-- Client Create Modal -->
  <div v-if="showModal" class="modal-backdrop fade show" style="background: rgba(0,0,0,0.6);"></div>
  <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content border-0 shadow" style="background: #1e1e1e;">
        <div class="modal-header" style="background: #2a2a2a; border-bottom: 1px solid #444;">
          <h5 class="modal-title fw-bold text-light"><i class="bi bi-person-plus me-2"></i>Add New Client</h5>
          <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
        </div>
        <div class="modal-body p-4">
          <form @submit.prevent="submitClient">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label text-light">Client Name *</label>
                <input type="text" v-model="form.name" class="form-control modal-input" required>
                <small class="text-danger" v-if="clientStore.errors.name">{{ clientStore.errors.name[0] }}</small>
              </div>
              <div class="col-md-6">
                <label class="form-label text-light">Phone Number *</label>
                <input type="text" v-model="form.phone" class="form-control modal-input" required>
                <small class="text-danger" v-if="clientStore.errors.phone">{{ clientStore.errors.phone[0] }}</small>
              </div>
              <div class="col-md-6">
                <label class="form-label text-light">Email Address</label>
                <input type="email" v-model="form.email" class="form-control modal-input">
                <small class="text-danger" v-if="clientStore.errors.email">{{ clientStore.errors.email[0] }}</small>
              </div>
              <div class="col-md-6">
                <label class="form-label text-light">Opening Due Amount (৳)</label>
                <input type="number" v-model="form.total_due" class="form-control modal-input">
                <small class="text-danger" v-if="clientStore.errors.total_due">{{ clientStore.errors.total_due[0] }}</small>
              </div>
              <div class="col-12">
                <label class="form-label text-light">Address</label>
                <textarea v-model="form.address" class="form-control modal-input" rows="3"></textarea>
                <small class="text-danger" v-if="clientStore.errors.address">{{ clientStore.errors.address[0] }}</small>
              </div>
              <div class="col-12 mt-4 text-end">
                <button type="button" class="btn btn-light me-2" @click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="clientStore.loading">
                  <span v-if="clientStore.loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-check-circle me-1"></i> Save Client
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Receive Payment Modal -->
  <div v-if="showPayModal" class="modal-backdrop fade show" style="background: rgba(0,0,0,0.6);"></div>
  <div v-if="showPayModal" class="modal fade show d-block" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow" style="background: #1e1e1e;">
        <div class="modal-header" style="background: #2a2a2a; border-bottom: 1px solid #444;">
          <h5 class="modal-title fw-bold text-light"><i class="bi bi-cash me-2"></i>Receive Payment</h5>
          <button type="button" class="btn-close btn-close-white" @click="closePayModal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <span class="text-light">Client: <strong>{{ dropdownClient?.name }}</strong></span>
            <span class="ms-3 text-danger fw-bold">Due: ৳{{ dropdownClient?.due_amount }}</span>
          </div>
          <div class="mb-3">
            <label class="form-label text-light">Amount (৳) *</label>
            <input type="number" v-model="payAmount" class="form-control modal-input" min="1" :max="dropdownClient?.due_amount" required>
          </div>
          <div class="mb-3">
            <label class="form-label text-light">Payment Method *</label>
            <select v-model="payMethod" class="form-select modal-input" required>
              <option value="cash">Cash</option>
              <option value="bkash">bKash</option>
              <option value="bank">Bank Transfer</option>
              <option value="card">Card</option>
            </select>
          </div>
          <div class="text-end">
            <button type="button" class="btn btn-light me-2" @click="closePayModal">Cancel</button>
            <button type="button" class="btn btn-success px-4" @click="submitPayment" :disabled="paySubmitting">
              <span v-if="paySubmitting" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-1"></i> Confirm
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import { useClientStore } from '../../../store/clients';

const clientStore = useClientStore();
const searchQuery = ref('');
const openDropdown = ref(null);
const dropdownPos = ref({ top: 0, left: 0 });
const dropdownClient = ref(null);
let searchTimeout = null;

const showModal = ref(false);
const form = ref({ name: '', phone: '', email: '', total_due: 0, address: '' });

const showPayModal = ref(false);
const payAmount = ref(0);
const payMethod = ref('cash');
const paySubmitting = ref(false);

const toggleDropdown = (id, event) => {
  if (openDropdown.value === id) {
    openDropdown.value = null;
    return;
  }
  const rect = event.target.getBoundingClientRect();
  let leftPos = rect.right - 160;
  
  if (rect.bottom + 380 > window.innerHeight) {
    // Dropup (open upwards from the button)
    dropdownPos.value = { 
      bottom: (window.innerHeight - rect.top + 4) + 'px', 
      left: leftPos + 'px',
      top: 'auto'
    };
  } else {
    // Dropdown (open downwards from the button)
    dropdownPos.value = { 
      top: (rect.bottom + 4) + 'px', 
      left: leftPos + 'px',
      bottom: 'auto'
    };
  }

  dropdownClient.value = clientStore.clients.find(c => c.id === id);
  openDropdown.value = id;
};

const closeDropdown = () => {
  openDropdown.value = null;
};

const handleToggleStatus = async () => {
  const newStatus = await clientStore.toggleStatus(dropdownClient.value.id);
  if (newStatus) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Client ${newStatus === 'active' ? 'activated' : 'deactivated'}!`, showConfirmButton: false, timer: 2000 });
  }
  openDropdown.value = null;
};

const openPayModal = () => {
  payAmount.value = dropdownClient.value.due_amount;
  payMethod.value = 'cash';
  showPayModal.value = true;
  openDropdown.value = null;
};

const closePayModal = () => {
  showPayModal.value = false;
  paySubmitting.value = false;
};

const submitPayment = async () => {
  if (!payAmount.value || payAmount.value <= 0) return;
  paySubmitting.value = true;
  try {
    await axios.post(`/api/clients/${dropdownClient.value.id}/receive-payment`, {
      amount: payAmount.value,
      payment_method: payMethod.value
    });
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Payment received!', showConfirmButton: false, timer: 2000 });
    closePayModal();
    clientStore.fetchClients(clientStore.page, searchQuery.value);
  } catch (error) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: error.response?.data?.message || 'Payment failed', showConfirmButton: false, timer: 3000 });
    paySubmitting.value = false;
  }
};

const openModal = () => {
  clientStore.errors = {};
  form.value = { name: '', phone: '', email: '', total_due: 0, address: '' };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  clientStore.errors = {};
};

const submitClient = async () => {
  const success = await clientStore.createClient(form.value);
  if (success) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Client added successfully!', showConfirmButton: false, timer: 3000 });
    clientStore.fetchClients(clientStore.page, searchQuery.value);
    closeModal();
  }
};

onMounted(() => {
  clientStore.fetchClients();
  document.addEventListener('click', closeDropdown);
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
</script>

<style scoped>
.dropdown-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 999;
}
.modal-input {
  background-color: #2a2a2a !important;
  color: #fff !important;
  border-color: #444 !important;
}
.modal-input:focus {
  background-color: #333 !important;
  color: #fff !important;
  border-color: #666 !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
</style>

<style>
.custom-dropdown-menu {
  position: fixed;
  z-index: 1000;
  min-width: 180px;
  padding: 0.5rem 0;
  margin: 0;
  background-color: #2b3035;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 0.5rem;
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  list-style: none;
}
.custom-dropdown-menu .dropdown-item {
  display: flex;
  align-items: center;
  padding: 0.5rem 1rem;
  color: #dee2e6;
  text-decoration: none;
  transition: background 0.15s;
}
.custom-dropdown-menu .dropdown-item:hover {
  background-color: rgba(255,255,255,0.08);
  color: #fff;
}
.custom-dropdown-menu .dropdown-item.text-danger {
  color: #dc3545 !important;
}
.custom-dropdown-menu .dropdown-item.text-danger:hover {
  background-color: rgba(220,53,69,0.15);
}
.custom-dropdown-menu .dropdown-divider {
  border-color: rgba(255,255,255,0.1);
  margin: 0.25rem 0;
}
</style>
