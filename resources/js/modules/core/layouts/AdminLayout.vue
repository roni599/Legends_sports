<template>
  <div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar d-none d-md-flex">
      <div class="sidebar-header">
        <h1 class="sidebar-brand m-0 fs-5">LEGENDS ARENA</h1>
      </div>
      <nav class="sidebar-nav">
        <router-link to="/" class="nav-item-custom" active-class="active">
          Dashboard
        </router-link>
        <a href="#" class="nav-item-custom">Bookings</a>
        <a href="#" class="nav-item-custom">POS & Sales</a>
        <a href="#" class="nav-item-custom">Clients</a>
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
