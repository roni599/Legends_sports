<template>
  <div class="content-card">
    <div class="content-header">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h3 class="fs-5 m-0 text-light">Due Paid List</h3>
        <div class="input-group" style="max-width: 300px;">
          <span class="input-group-text bg-dark border-secondary text-light">
            <i class="bi bi-search"></i>
          </span>
          <input type="text" class="form-control bg-dark border-secondary text-light" placeholder="Search..." v-model="search" @input="debounceSearch">
        </div>
      </div>
    </div>
    
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover table-bordered align-middle">
          <thead style="background-color: #444;">
            <tr>
              <th>SL</th>
              <th>Date</th>
              <th>Transaction / Invoice No</th>
              <th>Client Name</th>
              <th>Amount (৳)</th>
              <th>Payment Method</th>
              <th>Paid By</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">Loading data...</td>
            </tr>
            <tr v-else-if="transactions.length === 0">
              <td colspan="8" class="text-center py-4">No transactions found.</td>
            </tr>
            <tr v-else v-for="(tx, index) in transactions" :key="tx.id">
              <td>{{ (currentPage - 1) * 15 + index + 1 }}</td>
              <td>{{ formatDate(tx.created_at) }}</td>
              <td>{{ tx.invoice ? tx.invoice.invoice_number : tx.transaction_id }}</td>
              <td>{{ tx.client ? tx.client.name : 'N/A' }}</td>
              <td class="text-danger fw-bold">{{ tx.amount }}</td>
              <td class="text-capitalize">{{ tx.payment_method }}</td>
              <td>{{ tx.user ? tx.user.name : 'System' }}</td>
              <td>
                <button class="btn btn-sm btn-primary me-2" @click="goToEditPage(tx)" title="Edit">
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-info" @click="viewInvoice(tx)" title="View">
                  <i class="bi bi-eye"></i> View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <nav v-if="totalPages > 1" class="mt-4">
        <ul class="pagination justify-content-end mb-0 pagination-dark">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">Previous</a>
          </li>
          <li class="page-item" v-for="page in totalPages" :key="page" :class="{ active: page === currentPage }">
            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">Next</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const router = useRouter();
const transactions = ref([]);
const loading = ref(true);
const search = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
let searchTimeout = null;

const fetchTransactions = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/api/client-transactions', {
      params: { type: 'due_paid', page, search: search.value }
    });
    transactions.value = res.data.data;
    currentPage.value = res.data.current_page;
    totalPages.value = res.data.last_page;
  } catch (error) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Failed to load data', showConfirmButton: false, timer: 3000 });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchTransactions();
});

const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchTransactions(1), 500);
};

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) fetchTransactions(page);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const goToEditPage = (tx) => {
  router.push(`/clients/transactions/due-paid/${tx.id}/edit`);
};

const viewInvoice = (tx) => {
  if (tx.invoice_id) {
    router.push(`/print-invoice/${tx.invoice_id}`);
  } else {
    Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Payment Receipt viewing will be available soon', showConfirmButton: false, timer: 3000 });
  }
};
</script>

<style scoped>
/* Removed modal styles */
</style>
