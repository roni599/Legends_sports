<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Supplier Management</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search name or company..." style="width: 200px;">
        <button class="btn btn-primary btn-sm" @click="openSupplierModal(null)">
          + Add Supplier
        </button>
      </div>
    </div>
    
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Company</th>
              <th>Phone</th>
              <th class="text-end">Balance (৳)</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="supplierStore.loading">
              <td colspan="6" class="text-center py-4">Loading suppliers...</td>
            </tr>
            <tr v-else-if="supplierStore.suppliers.length === 0">
              <td colspan="6" class="text-center py-4">No suppliers found</td>
            </tr>
            <tr v-else v-for="supplier in supplierStore.suppliers" :key="supplier.id">
              <td>#{{ supplier.id }}</td>
              <td>{{ supplier.name }}</td>
              <td>{{ supplier.company || '-' }}</td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span>{{ supplier.phone || '-' }}</span>
                  <a v-if="supplier.phone" 
                     :href="'https://wa.me/' + (supplier.phone.startsWith('0') ? '88' + supplier.phone : supplier.phone).replace(/[^0-9]/g, '')" 
                     target="_blank" 
                     class="btn btn-sm btn-success p-1 lh-1" 
                     title="Quick WhatsApp Message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                  </a>
                </div>
              </td>
              <td class="text-end" :class="{'text-danger fw-bold': supplier.balance > 0}">{{ supplier.balance }}</td>
              <td class="text-end">
                <button @click="openLedgerModal(supplier)" class="btn btn-sm btn-outline-warning me-2">Ledger</button>
                <button @click="openPayModal(supplier)" class="btn btn-sm btn-outline-success me-2">Pay / Refund</button>
                <button @click="openSupplierModal(supplier)" class="btn btn-sm btn-outline-info me-2">Edit</button>
                <button @click="supplierStore.deleteSupplier(supplier.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-secondary small">Showing {{ supplierStore.suppliers.length }} entries of {{ supplierStore.total }}</span>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="supplierStore.page === 1" @click="changePage(supplierStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="supplierStore.page * supplierStore.perPage >= supplierStore.total" @click="changePage(supplierStore.page + 1)">Next</button>
        </div>
      </div>
    </div>

    <!-- Supplier Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">{{ editingSupplierId ? 'Edit Supplier' : 'Add New Supplier' }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="submitSupplier">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Contact Name *</label>
                <input type="text" v-model="supplierForm.name" class="form-control custom-input" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Company Name</label>
                <input type="text" v-model="supplierForm.company" class="form-control custom-input">
              </div>
              <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" v-model="supplierForm.phone" class="form-control custom-input">
              </div>
              <div class="mb-3" v-if="!editingSupplierId">
                <label class="form-label">Initial Balance (৳)</label>
                <input type="number" v-model.number="supplierForm.balance" class="form-control custom-input" min="0" step="0.01">
                <div class="form-text text-secondary">Outstanding amount you owe to this supplier.</div>
              </div>
            </div>
            <div class="modal-footer border-secondary">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="closeSupplierModalBtn">Close</button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                {{ editingSupplierId ? 'Update' : 'Save' }} Supplier
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Ledger Modal -->
    <div class="modal fade" id="ledgerModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Supplier Ledger: {{ activeSupplier?.name }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-0">
            <div class="p-3 bg-black border-bottom border-secondary d-flex justify-content-between">
              <span>Current Balance:</span>
              <span class="fs-5 fw-bold" :class="activeSupplier?.balance > 0 ? 'text-danger' : (activeSupplier?.balance < 0 ? 'text-success' : 'text-light')">
                ৳{{ Math.abs(activeSupplier?.balance || 0) }} {{ activeSupplier?.balance < 0 ? '(Advance)' : (activeSupplier?.balance > 0 ? '(Due)' : '') }}
              </span>
            </div>
            <div class="table-responsive" style="max-height: 400px;">
              <table class="table table-dark table-striped mb-0">
                <thead class="sticky-top bg-dark">
                  <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Ref No</th>
                    <th class="text-end">Amount (৳)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="ledgerLoading">
                    <td colspan="4" class="text-center py-4">Loading ledger...</td>
                  </tr>
                  <tr v-else-if="ledgerData.length === 0">
                    <td colspan="4" class="text-center py-4">No transactions found</td>
                  </tr>
                  <tr v-else v-for="tx in ledgerData" :key="tx.id + tx.type">
                    <td>{{ new Date(tx.date).toLocaleDateString() }}</td>
                    <td>
                      <span class="badge" :class="{
                        'bg-warning text-dark': tx.type === 'Purchase',
                        'bg-success': tx.type === 'Payment Made',
                        'bg-info text-dark': tx.type === 'Refund Received'
                      }">{{ tx.type }}</span>
                    </td>
                    <td>{{ tx.reference_no || '-' }}</td>
                    <td class="text-end fw-bold" :class="tx.type === 'Purchase' ? 'text-danger' : 'text-success'">
                      {{ tx.amount }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pay / Refund Modal -->
    <div class="modal fade" id="payModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Transaction: {{ activeSupplier?.name }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="submitPayment">
            <div class="modal-body">
              <div class="alert alert-info bg-dark text-info border-info">
                Current Balance: <strong>৳{{ Math.abs(activeSupplier?.balance || 0) }}</strong>
                {{ activeSupplier?.balance < 0 ? '(Advance)' : (activeSupplier?.balance > 0 ? '(Due)' : '') }}
              </div>
              <div class="mb-3">
                <label class="form-label">Transaction Type *</label>
                <select v-model="payForm.type" class="form-select custom-input" required>
                  <option value="pay">Pay Supplier (Send Money)</option>
                  <option value="refund">Receive Refund (Get Money)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Amount (৳) *</label>
                <input type="number" v-model.number="payForm.amount" class="form-control custom-input" min="1" step="0.01" required>
                <div class="form-text text-secondary" v-if="payForm.type === 'pay'">
                  Paying more than the due will result in an Advance Balance.
                </div>
              </div>
            </div>
            <div class="modal-footer border-secondary">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="closePayModalBtn">Close</button>
              <button type="submit" class="btn" :class="payForm.type === 'pay' ? 'btn-success' : 'btn-info'" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                {{ payForm.type === 'pay' ? 'Submit Payment' : 'Receive Refund' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSupplierStore } from '../../../store/suppliers';

const supplierStore = useSupplierStore();
const searchQuery = ref('');
let searchTimeout = null;

// Modals state
const closeSupplierModalBtn = ref(null);
const closePayModalBtn = ref(null);
let bsSupplierModal = null;
let bsLedgerModal = null;
let bsPayModal = null;

const isSubmitting = ref(false);
const editingSupplierId = ref(null);
const activeSupplier = ref(null);

const ledgerData = ref([]);
const ledgerLoading = ref(false);

const payForm = ref({
  type: 'pay',
  amount: 0
});

const supplierForm = ref({
  name: '',
  company: '',
  phone: '',
  balance: 0
});

onMounted(() => {
  bsSupplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));
  bsLedgerModal = new bootstrap.Modal(document.getElementById('ledgerModal'));
  bsPayModal = new bootstrap.Modal(document.getElementById('payModal'));
  supplierStore.fetchSuppliers();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    supplierStore.fetchSuppliers(1, searchQuery.value);
  }, 300);
};

const changePage = (page) => {
  supplierStore.fetchSuppliers(page, searchQuery.value);
};

const openSupplierModal = (supplier = null) => {
  if (supplier) {
    editingSupplierId.value = supplier.id;
    supplierForm.value = {
      name: supplier.name,
      company: supplier.company || '',
      phone: supplier.phone || '',
      balance: supplier.balance
    };
  } else {
    editingSupplierId.value = null;
    supplierForm.value = {
      name: '',
      company: '',
      phone: '',
      balance: 0
    };
  }
  bsSupplierModal.show();
};

const submitSupplier = async () => {
  isSubmitting.value = true;
  try {
    if (editingSupplierId.value) {
      await supplierStore.updateSupplier(editingSupplierId.value, supplierForm.value);
    } else {
      await supplierStore.addSupplier(supplierForm.value);
    }
    closeSupplierModalBtn.value.click();
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to save supplier');
  } finally {
    isSubmitting.value = false;
  }
};

const openLedgerModal = async (supplier) => {
  activeSupplier.value = supplier;
  ledgerData.value = [];
  bsLedgerModal.show();
  
  ledgerLoading.value = true;
  try {
    ledgerData.value = await supplierStore.fetchLedger(supplier.id);
  } catch (e) {
    alert('Failed to load ledger');
    bsLedgerModal.hide();
  } finally {
    ledgerLoading.value = false;
  }
};

const openPayModal = (supplier) => {
  activeSupplier.value = supplier;
  payForm.value = {
    type: 'pay',
    amount: Math.max(0, supplier.balance) || 1
  };
  bsPayModal.show();
};

const submitPayment = async () => {
  isSubmitting.value = true;
  try {
    if (payForm.value.type === 'pay') {
      await supplierStore.paySupplier(activeSupplier.value.id, payForm.value.amount);
    } else {
      await supplierStore.receiveRefund(activeSupplier.value.id, payForm.value.amount);
    }
    closePayModalBtn.value.click();
    alert('Transaction completed successfully!');
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to process transaction');
  } finally {
    isSubmitting.value = false;
  }
};
</script>
