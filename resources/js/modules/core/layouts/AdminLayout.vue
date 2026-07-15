<template>
  <div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar d-none d-md-flex">
      <div class="sidebar-header">
        <h1 class="sidebar-brand m-0 fs-5">LEGENDS ARENA</h1>
      </div>
      <nav class="sidebar-nav">
        <!-- Dashboard -->
        <router-link to="/" class="nav-item-custom" active-class="active">
          Dashboard
        </router-link>

        <!-- Clients Module -->
        <div class="nav-item-group" v-if="authStore.hasRole('super-admin') || authStore.hasRole('manager') || authStore.hasRole('booking-manager')">
          <a class="nav-item-custom" data-bs-toggle="collapse" href="#clientMenu" role="button" aria-expanded="false" aria-controls="clientMenu">
            Clients ▾
          </a>
          <div class="collapse" id="clientMenu">
            <div class="ps-3 pe-2 py-1 border-start border-secondary ms-3 mb-2 mt-1">
              <router-link to="/clients" class="nav-item-custom text-sm mb-1 py-1" active-class="active">Client List</router-link>
              <router-link to="/clients/create" class="nav-item-custom text-sm py-1" active-class="active">Add New Client</router-link>
            </div>
          </div>
        </div>

        <!-- Booking Module -->
        <div class="nav-item-group" v-if="authStore.hasRole('super-admin') || authStore.hasRole('manager') || authStore.hasRole('booking-manager')">
          <a class="nav-item-custom" data-bs-toggle="collapse" href="#bookingMenu" role="button" aria-expanded="false" aria-controls="bookingMenu">
            Bookings ▾
          </a>
          <div class="collapse" id="bookingMenu">
            <div class="ps-3 pe-2 py-1 border-start border-secondary ms-3 mb-2 mt-1">
              <router-link to="/bookings/calendar" class="nav-item-custom text-sm mb-1 py-1" active-class="active">Calendar View</router-link>
              <router-link to="/bookings" class="nav-item-custom text-sm py-1" active-class="active">Booking List</router-link>
            </div>
          </div>
        </div>

        <!-- Ground Module -->
        <div class="nav-item-group" v-if="authStore.hasRole('super-admin')">
          <a class="nav-item-custom" data-bs-toggle="collapse" href="#groundMenu" role="button" aria-expanded="false" aria-controls="groundMenu">
            Ground Setup ▾
          </a>
          <div class="collapse" id="groundMenu">
            <div class="ps-3 pe-2 py-1 border-start border-secondary ms-3 mb-2 mt-1">
              <router-link to="/grounds" class="nav-item-custom text-sm mb-1 py-1" active-class="active">Ground List</router-link>
              <router-link to="/grounds/pricing" class="nav-item-custom text-sm py-1" active-class="active">Pricing Rules</router-link>
            </div>
          </div>
        </div>

        <!-- Settings Module -->
        <div class="nav-item-group" v-if="authStore.hasRole('super-admin')">
          <a class="nav-item-custom" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="false" aria-controls="settingsMenu">
            Settings ▾
          </a>
          <div class="collapse" id="settingsMenu">
            <div class="ps-3 pe-2 py-1 border-start border-secondary ms-3 mb-2 mt-1">
              <router-link to="/settings/roles" class="nav-item-custom text-sm mb-1 py-1" active-class="active">User Roles & Permissions</router-link>
            </div>
          </div>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top header -->
      <header class="top-header">
        <div>
          <button class="btn btn-outline-light d-md-none border-0">
            ☰
          </button>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="fs-6 fw-medium text-light">Admin</span>
          <button @click="handleLogout" class="btn btn-danger btn-sm px-3">Logout</button>
        </div>
      </header>

      <!-- Main view area -->
      <main class="main-view">
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../../store/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>
