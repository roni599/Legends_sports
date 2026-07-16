<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1 text-primary fw-bold">Cash Flow Report</h4>
        <p class="text-muted mb-0">View and export liquid cash transactions</p>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <form @submit.prevent="fetchData" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label text-sm fw-medium">Start Date</label>
            <input type="date" class="form-control" v-model="filter.start_date" required>
          </div>
          <div class="col-md-4">
            <label class="form-label text-sm fw-medium">End Date</label>
            <input type="date" class="form-control" v-model="filter.end_date" required>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary" :disabled="isLoading">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-funnel me-1"></i> Filter Data
            </button>
            <button type="button" class="btn btn-outline-danger" @click="exportReport('pdf')" :disabled="isLoading || data.length === 0">
              <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
            <button type="button" class="btn btn-outline-success" @click="exportReport('excel')" :disabled="isLoading || data.length === 0">
              <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </button>
            <button type="button" class="btn btn-outline-secondary" @click="printReport" :disabled="isLoading || data.length === 0">
              <i class="bi bi-printer me-1"></i> Print
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm" id="report-print-area">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-none d-print-block text-center">
        <h4 class="fw-bold">Legends Sports Arena</h4>
        <h5 class="mb-1">Cash Flow Report</h5>
        <p class="text-muted mb-3">{{ filter.start_date }} to {{ filter.end_date }}</p>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Date</th>
                <th>Transaction ID</th>
                <th>Method</th>
                <th class="text-end">Cash In (৳)</th>
                <th class="text-end pe-4">Cash Out (৳)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td colspan="5" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="data.length === 0">
                <td colspan="5" class="text-center py-5 text-muted">
                  No records found for the selected date range.
                </td>
              </tr>
              <tr v-else v-for="(row, index) in data" :key="index">
                <td class="ps-4">{{ row['Date'] }}</td>
                <td>{{ row['Transaction ID'] || 'N/A' }}</td>
                <td>
                  <span class="badge" :class="row['Type'] === 'IN' ? 'bg-success' : 'bg-danger'">{{ row['Type'] }}</span>
                  <span class="ms-2 text-muted">{{ row['Method'] }}</span>
                </td>
                <td class="text-end text-success fw-medium">{{ parseFloat(row['Amount (In)']).toLocaleString() }}</td>
                <td class="text-end text-danger fw-medium pe-4">{{ parseFloat(row['Amount (Out)']).toLocaleString() }}</td>
              </tr>
            </tbody>
            <tfoot class="table-light fw-bold" v-if="!isLoading && data.length > 0">
              <tr>
                <td colspan="3" class="text-end ps-4">TOTAL CASH FLOW:</td>
                <td class="text-end text-success">৳ {{ parseFloat(summary.total_in).toLocaleString() }}</td>
                <td class="text-end text-danger pe-4">৳ {{ parseFloat(summary.total_out).toLocaleString() }}</td>
              </tr>
              <tr class="table-primary text-center">
                <td colspan="5" class="py-3 fs-5">
                  NET CASH BALANCE: 
                  <span :class="(summary.total_in - summary.total_out) >= 0 ? 'text-success' : 'text-danger'">
                    ৳ {{ parseFloat(summary.total_in - summary.total_out).toLocaleString() }}
                  </span>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const isLoading = ref(false);
const data = ref([]);
const summary = reactive({
  total_in: 0,
  total_out: 0
});

const filter = reactive({
  start_date: '',
  end_date: ''
});

onMounted(() => {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
  const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
  
  filter.start_date = firstDay;
  filter.end_date = lastDay;
  
  fetchData();
});

const fetchData = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/reports/cash-flow', {
      params: {
        start_date: filter.start_date,
        end_date: filter.end_date,
        format: 'json'
      }
    });
    // Remove the last 'TOTAL SUMMARY' row sent by backend
    const rows = res.data.data;
    if (rows.length > 0 && rows[rows.length - 1].Date === 'TOTAL SUMMARY') {
      rows.pop();
    }
    data.value = rows;
    summary.total_in = res.data.total_in;
    summary.total_out = res.data.total_out;
  } catch (error) {
    console.error('Failed to fetch report data', error);
  } finally {
    isLoading.value = false;
  }
};

const exportReport = (format) => {
  const url = `/api/reports/cash-flow?start_date=${filter.start_date}&end_date=${filter.end_date}&format=${format}`;
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', '');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const printReport = () => {
  const printContents = document.getElementById('report-print-area').innerHTML;
  const originalContents = document.body.innerHTML;

  document.body.innerHTML = printContents;
  window.print();
  document.body.innerHTML = originalContents;
  window.location.reload(); // Reload to restore Vue reactivity bindings
};
</script>
