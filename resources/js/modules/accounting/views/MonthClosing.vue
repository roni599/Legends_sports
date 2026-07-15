<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Month Closing (Lock Accounting)</h3>
    </div>
    
    <div class="p-4 row">
      <div class="col-md-5">
        <div class="card bg-dark border-secondary text-light mb-4">
          <div class="card-body">
            <h5 class="card-title text-warning">Lock New Month</h5>
            <p class="text-secondary small">
              Locking a month will prevent any future changes to bookings, expenses, or POS sales for that month. This is required for accurate month-end financial reporting.
            </p>
            <form @submit.prevent="submitClosing" class="mt-4">
              <div class="mb-3">
                <label class="form-label">Select Month to Lock *</label>
                <input type="month" v-model="selectedMonth" class="form-control custom-input" required>
              </div>
              <button type="submit" class="btn btn-danger w-100 fw-bold" :disabled="accountingStore.loading">
                <span v-if="accountingStore.loading" class="spinner-border spinner-border-sm me-1"></span>
                🔒 Lock Month Permanently
              </button>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-md-7">
        <h5 class="text-light mb-3">Locked Months History</h5>
        <div class="table-responsive">
          <table class="table table-dark table-striped align-middle border border-secondary">
            <thead>
              <tr>
                <th>Month</th>
                <th>Locked By</th>
                <th>Lock Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="accountingStore.loading && accountingStore.monthlyClosings.length === 0">
                <td colspan="4" class="text-center py-4">Loading history...</td>
              </tr>
              <tr v-else-if="accountingStore.monthlyClosings.length === 0">
                <td colspan="4" class="text-center py-4 text-secondary">No months have been locked yet.</td>
              </tr>
              <tr v-else v-for="closing in accountingStore.monthlyClosings" :key="closing.id">
                <td class="fw-bold">{{ formatMonth(closing.month_year) }}</td>
                <td>{{ closing.closer?.name || 'System' }}</td>
                <td>{{ new Date(closing.created_at).toLocaleDateString() }}</td>
                <td><span class="badge bg-danger">🔒 Locked</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAccountingStore } from '../../../store/accounting';

const accountingStore = useAccountingStore();
const selectedMonth = ref('');

onMounted(() => {
  accountingStore.fetchMonthlyClosings();
  
  // Set default to last month
  const date = new Date();
  date.setMonth(date.getMonth() - 1);
  const m = String(date.getMonth() + 1).padStart(2, '0');
  selectedMonth.value = `${date.getFullYear()}-${m}`;
});

const formatMonth = (monthStr) => {
  const [year, month] = monthStr.split('-');
  const date = new Date(year, month - 1);
  return date.toLocaleString('default', { month: 'long', year: 'numeric' });
};

const submitClosing = async () => {
  if (!selectedMonth.value) return;
  await accountingStore.closeMonth(selectedMonth.value);
};
</script>
