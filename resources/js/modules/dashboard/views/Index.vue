<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1 text-primary fw-bold">Business Dashboard</h4>
        <p class="text-muted mb-0">Overview of today's activities and performance</p>
      </div>
      <div>
        <button class="btn btn-outline-primary shadow-sm" @click="fetchDashboardData" :disabled="isLoading">
          <i class="bi bi-arrow-clockwise me-1" :class="{'spin': isLoading}"></i> Refresh Data
        </button>
      </div>
    </div>
    
    <!-- Top KPI Widgets -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-primary bg-gradient text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="mb-1 text-white-50 fw-bold text-uppercase">Today's Revenue</p>
                <h3 class="fw-bold mb-0">৳ {{ kpi.todayRevenue }}</h3>
              </div>
              <div class="bg-white bg-opacity-25 rounded p-2">
                <i class="bi bi-wallet2 fs-4"></i>
              </div>
            </div>
            <div class="mt-3 text-sm">
              <span class="text-white fw-bold">{{ kpi.todayBookings }}</span> bookings today
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-success bg-gradient text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="mb-1 text-white-50 fw-bold text-uppercase">Monthly Revenue</p>
                <h3 class="fw-bold mb-0">৳ {{ kpi.monthlyRevenue }}</h3>
              </div>
              <div class="bg-white bg-opacity-25 rounded p-2">
                <i class="bi bi-graph-up fs-4"></i>
              </div>
            </div>
            <div class="mt-3 text-sm">
              <span class="text-white fw-bold">{{ kpi.monthlyBookings }}</span> total bookings this month
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-danger bg-gradient text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="mb-1 text-white-50 fw-bold text-uppercase">Total Market Due</p>
                <h3 class="fw-bold mb-0">৳ {{ kpi.totalDue }}</h3>
              </div>
              <div class="bg-white bg-opacity-25 rounded p-2">
                <i class="bi bi-exclamation-triangle fs-4"></i>
              </div>
            </div>
            <div class="mt-3 text-sm">
              From <span class="text-white fw-bold">{{ kpi.dueClientsCount }}</span> clients
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-info bg-gradient text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="mb-1 text-white-50 fw-bold text-uppercase">Active Grounds</p>
                <h3 class="fw-bold mb-0">{{ kpi.activeGrounds }}</h3>
              </div>
              <div class="bg-white bg-opacity-25 rounded p-2">
                <i class="bi bi-layers fs-4"></i>
              </div>
            </div>
            <div class="mt-3 text-sm">
              Ready for bookings
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Chart Area -->
      <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h5 class="fw-bold text-secondary">Revenue Trend (Last 7 Days)</h5>
          </div>
          <div class="card-body">
            <Line v-if="chartData.labels" :data="chartData" :options="chartOptions" style="height: 300px;" />
            <div v-else class="d-flex justify-content-center align-items-center h-100">
              <div class="spinner-border text-primary"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Bookings -->
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between">
            <h5 class="fw-bold text-secondary">Recent Bookings</h5>
            <router-link :to="{ name: 'BookingList' }" class="text-decoration-none text-sm">View All</router-link>
          </div>
          <div class="card-body p-0 pt-3">
            <ul class="list-group list-group-flush">
              <li class="list-group-item px-4 py-3" v-for="booking in recentBookings" :key="booking.id">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-1 fw-bold">{{ booking.client.name }}</h6>
                    <small class="text-muted">{{ booking.ground.name }}</small>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-primary">৳{{ booking.total_amount }}</div>
                    <span class="badge" :class="booking.status === 'confirmed' ? 'bg-success' : 'bg-warning text-dark'">
                      {{ booking.status }}
                    </span>
                  </div>
                </div>
              </li>
              <li class="list-group-item px-4 py-4 text-center text-muted" v-if="recentBookings.length === 0">
                No recent bookings found.
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

const isLoading = ref(false);
const recentBookings = ref([]);
const kpi = ref({
  todayRevenue: 0,
  todayBookings: 0,
  monthlyRevenue: 0,
  monthlyBookings: 0,
  totalDue: 0,
  dueClientsCount: 0,
  activeGrounds: 0
});

const chartData = ref({});
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        borderDash: [5, 5]
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
};

const fetchDashboardData = async () => {
  isLoading.value = true;
  try {
    const [statsRes, chartRes, bookingsRes] = await Promise.all([
      axios.get('/api/dashboard/stats'),
      axios.get('/api/dashboard/chart'),
      axios.get('/api/bookings')
    ]);

    const stats = statsRes.data;
    kpi.value.todayRevenue = parseFloat(stats.today_revenue || 0).toLocaleString();
    kpi.value.monthlyRevenue = parseFloat(stats.monthly_revenue || 0).toLocaleString();
    kpi.value.totalDue = parseFloat(stats.total_due || 0).toLocaleString();
    kpi.value.activeGrounds = stats.active_bookings;

    // We don't have these exact counts in the new backend, so hide or mock them
    kpi.value.todayBookings = '-';
    kpi.value.monthlyBookings = '-';
    kpi.value.dueClientsCount = '-';

    recentBookings.value = bookingsRes.data.data ? bookingsRes.data.data.slice(0, 5) : [];

    chartData.value = {
      labels: chartRes.data.labels,
      datasets: [
        {
          label: 'Revenue (৳)',
          backgroundColor: '#0d6efd',
          borderColor: '#0d6efd',
          tension: 0.4,
          data: chartRes.data.data
        }
      ]
    };

  } catch (error) {
    console.error('Failed to load dashboard data', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<style scoped>
.spin {
  animation: spin 1s linear infinite;
}
@keyframes spin { 
  100% { -webkit-transform: rotate(360deg); transform:rotate(360deg); } 
}
</style>
