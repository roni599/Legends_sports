<template>
  <div>
    <h2 class="fs-4 fw-semibold text-light mb-4">Dashboard Overview</h2>
    
    <!-- Widgets -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="dashboard-widget h-100">
          <h3 class="widget-title">Today's Bookings</h3>
          <p class="widget-value">{{ dashboardStore.todayBookings }}</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="dashboard-widget h-100">
          <h3 class="widget-title">Total Revenue</h3>
          <p class="widget-value revenue">৳ {{ dashboardStore.totalRevenue }}</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="dashboard-widget h-100">
          <h3 class="widget-title">Total Due</h3>
          <p class="widget-value due">৳ {{ dashboardStore.totalDue }}</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="dashboard-widget h-100">
          <h3 class="widget-title">Occupancy Rate</h3>
          <p class="widget-value occupancy">{{ dashboardStore.occupancyRate }}%</p>
        </div>
      </div>
    </div>

    <!-- Quick Actions / Upcoming Bookings -->
    <div class="content-card">
      <div class="content-header">
        <h3 class="fs-5 m-0 text-light">Recent Bookings</h3>
        <button class="btn btn-link text-decoration-none">View All</button>
      </div>
      <div class="p-4">
        <p class="text-secondary m-0">Booking table will appear here...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useDashboardStore } from '../../../store/dashboard';

const dashboardStore = useDashboardStore();
</script>
