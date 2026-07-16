<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1 text-primary fw-bold">{{ title }}</h4>
        <p class="text-muted mb-0">View and export records</p>
      </div>
    </div>

    <!-- Filter Card (Only for Sales) -->
    <div class="card border-0 shadow-sm mb-4" v-if="isDateFiltered">
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
          </div>
        </form>
      </div>
    </div>

    <!-- Actions & Data Table Card -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 d-none d-md-block">{{ title }}</h5>
        
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-danger btn-sm px-3" @click="exportReport('pdf')" :disabled="isLoading || data.length === 0">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
          </button>
          <button type="button" class="btn btn-outline-success btn-sm px-3" @click="exportReport('excel')" :disabled="isLoading || data.length === 0">
            <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm px-3" @click="printReport" :disabled="isLoading || data.length === 0">
            <i class="bi bi-printer me-1"></i> Print
          </button>
        </div>
      </div>
      
      <!-- Print Header (Hidden normally) -->
      <div id="print-header" class="d-none d-print-block text-center mb-4 mt-3">
        <h4 class="fw-bold">Legends Sports Arena</h4>
        <h5 class="mb-1">{{ title }}</h5>
        <p class="text-muted mb-3" v-if="isDateFiltered">{{ filter.start_date }} to {{ filter.end_date }}</p>
      </div>

      <div class="card-body p-0" id="report-print-area">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th v-for="(col, index) in columns" :key="index" :class="{'ps-4': index === 0, 'text-end pe-4': isNumericColumn(col)}">
                  {{ col }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="isLoading">
                <td :colspan="columns.length" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="paginatedData.length === 0 && !totalRow">
                <td :colspan="columns.length" class="text-center py-5 text-muted">
                  No records found.
                </td>
              </tr>
              <tr v-else v-for="(row, rIndex) in paginatedData" :key="rIndex">
                <td v-for="(col, cIndex) in columns" :key="cIndex" :class="{'ps-4': cIndex === 0, 'text-end pe-4': isNumericColumn(col)}" style="white-space: pre-line;">
                  {{ formatCell(col, row[col]) }}
                </td>
              </tr>
              
              <!-- Total Row (Always pinned at the bottom) -->
              <tr v-if="totalRow" class="table-active fw-bold">
                <td v-for="(col, cIndex) in columns" :key="cIndex" :class="{'ps-4': cIndex === 0, 'text-end pe-4': isNumericColumn(col)}">
                  {{ formatCell(col, totalRow[col]) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Custom Pagination -->
      <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center" v-if="actualData.length > 0">
        <div class="text-muted text-sm mb-2 mb-md-0">
          Showing {{ paginationStart }} to {{ paginationEnd }} of {{ actualData.length }} entries
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="currentPage === 1" @click="currentPage--">Previous</button>
          
          <button v-for="page in visiblePages" :key="page" class="btn btn-sm" :class="page === currentPage ? 'btn-primary' : 'btn-outline-secondary'" @click="currentPage = page">
            {{ page }}
          </button>
          
          <button class="btn btn-sm btn-outline-secondary" :disabled="currentPage === totalPages" @click="currentPage++">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const isLoading = ref(false);
const data = ref([]);
const columns = ref([]);
const title = ref('');

// Pagination state
const currentPage = ref(1);
const perPage = ref(10);

const filter = reactive({
  start_date: '',
  end_date: ''
});

const isDateFiltered = ref(false);

// Computed properties for pagination
const actualData = computed(() => {
  return data.value.filter(row => !isTotalRow(row));
});

const totalRow = computed(() => {
  return data.value.find(row => isTotalRow(row));
});

const totalPages = computed(() => {
  return Math.ceil(actualData.value.length / perPage.value) || 1;
});

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return actualData.value.slice(start, end);
});

const paginationStart = computed(() => {
  if (actualData.value.length === 0) return 0;
  return ((currentPage.value - 1) * perPage.value) + 1;
});

const paginationEnd = computed(() => {
  const end = currentPage.value * perPage.value;
  return end > actualData.value.length ? actualData.value.length : end;
});

const visiblePages = computed(() => {
  const pages = [];
  const maxVisible = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
  let end = start + maxVisible - 1;

  if (end > totalPages.value) {
    end = totalPages.value;
    start = Math.max(1, end - maxVisible + 1);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

const initReport = () => {
  const type = route.params.type;
  isDateFiltered.value = ['sales', 'income', 'expense', 'income-vs-expense'].includes(type);
  
  if (isDateFiltered.value) {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
    filter.start_date = firstDay;
    filter.end_date = lastDay;
  } else {
    filter.start_date = '';
    filter.end_date = '';
  }
  
  fetchData();
};

onMounted(() => {
  initReport();
});

watch(() => route.params.type, () => {
  if (route.name === 'GenericReport') {
    initReport();
  }
});

const fetchData = async () => {
  isLoading.value = true;
  try {
    const type = route.params.type;
    const params = { format: 'json' };
    
    if (isDateFiltered.value) {
      params.start_date = filter.start_date;
      params.end_date = filter.end_date;
    }
    
    const res = await axios.get(`/api/reports/${type}`, { params });
    columns.value = res.data.columns;
    data.value = res.data.data;
    title.value = res.data.title;
    currentPage.value = 1;
  } catch (error) {
    console.error('Failed to fetch report data', error);
  } finally {
    isLoading.value = false;
  }
};

const exportReport = (format) => {
  const type = route.params.type;
  let url = `/api/reports/${type}?format=${format}`;
  
  if (isDateFiltered.value) {
    url += `&start_date=${filter.start_date}&end_date=${filter.end_date}`;
  }
  
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', '');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const printReport = () => {
  // Temporarily disable pagination to print all rows
  const originalPerPage = perPage.value;
  perPage.value = actualData.value.length || 10000;
  
  // Wait for DOM to update with all rows
  setTimeout(() => {
    const printHeader = document.getElementById('print-header').outerHTML;
    const printArea = document.getElementById('report-print-area').outerHTML;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = `<div>${printHeader}${printArea}</div>`;
    window.print();
    
    // Restore original content and pagination
    document.body.innerHTML = originalContents;
    window.location.reload();
  }, 100);
};

const isNumericColumn = (colName) => {
  return colName.includes('(') || colName.toLowerCase().includes('amount') || colName.toLowerCase().includes('stock');
};

const isTotalRow = (row) => {
  return row[columns.value[0]] === 'TOTAL';
};

const formatCell = (colName, value) => {
  if (value === null || value === undefined || value === '') return '';
  if (isNumericColumn(colName) && !isNaN(value)) {
    return parseFloat(value).toLocaleString();
  }
  return value;
};
</script>

<style>
@media print {
  body {
    padding: 20px;
    background: #fff;
  }
  .table-light {
    background-color: #f8f9fa !important;
  }
}
</style>
