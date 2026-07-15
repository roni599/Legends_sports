<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <div class="mb-4">
        <h2 class="auth-title">Legends Arena</h2>
        <p class="auth-subtitle">Sign in to your account</p>
      </div>
      
      <form @submit.prevent="login">
        <!-- Error Message -->
        <div v-if="errorMessage" class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 p-2 mb-3 text-sm rounded">
          <i class="bi bi-exclamation-circle me-1"></i> {{ errorMessage }}
        </div>

        <div class="mb-3">
          <label class="form-label text-light">Email Address</label>
          <input type="email" v-model="email" class="form-control custom-input py-2" placeholder="admin@example.com" required>
        </div>
        <div class="mb-4">
          <label class="form-label text-light">Password</label>
          <input type="password" v-model="password" class="form-control custom-input py-2" placeholder="••••••••" required>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-2 fw-medium" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
          {{ isLoading ? 'Signing In...' : 'Sign In' }}
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
const isLoading = ref(false);
const errorMessage = ref('');

const login = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    await authStore.login(email.value, password.value);
    router.push('/');
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Invalid credentials or connection error';
  } finally {
    isLoading.value = false;
  }
};
</script>
