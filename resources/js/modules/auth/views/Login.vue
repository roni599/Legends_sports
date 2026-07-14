<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="mb-4">
        <h2 class="auth-title">Legends Arena</h2>
        <p class="auth-subtitle">Sign in to your account</p>
      </div>
      
      <form @submit.prevent="login">
        <div class="mb-3">
          <label class="form-label text-light">Email Address</label>
          <input type="email" v-model="email" class="form-control custom-input py-2" placeholder="admin@example.com" required>
        </div>
        <div class="mb-4">
          <label class="form-label text-light">Password</label>
          <input type="password" v-model="password" class="form-control custom-input py-2" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">
          Sign In
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../../store/auth';
import { useRouter } from 'vue-router';

const email = ref('');
const password = ref('');
const authStore = useAuthStore();
const router = useRouter();

const login = () => {
  // Mock login for now
  authStore.setAuthData({ id: 1, name: 'Admin', roles: ['super-admin'] }, 'fake-jwt-token');
  router.push('/');
};
</script>
