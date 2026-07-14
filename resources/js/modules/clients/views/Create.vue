<template>
  <div class="content-card">
    <div class="content-header">
      <h3 class="fs-5 m-0 text-light">Add New Client</h3>
      <router-link to="/clients" class="btn btn-outline-light btn-sm">
        Back to List
      </router-link>
    </div>
    <div class="p-4">
      <form @submit.prevent="saveClient">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label text-light">Client Name *</label>
            <input type="text" v-model="form.name" class="form-control custom-input" required>
            <small class="text-danger" v-if="clientStore.errors.name">{{ clientStore.errors.name[0] }}</small>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Phone Number *</label>
            <input type="text" v-model="form.phone" class="form-control custom-input" required>
            <small class="text-danger" v-if="clientStore.errors.phone">{{ clientStore.errors.phone[0] }}</small>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Email Address</label>
            <input type="email" v-model="form.email" class="form-control custom-input">
            <small class="text-danger" v-if="clientStore.errors.email">{{ clientStore.errors.email[0] }}</small>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Opening Due Amount (৳)</label>
            <input type="number" v-model="form.total_due" class="form-control custom-input">
            <small class="text-danger" v-if="clientStore.errors.total_due">{{ clientStore.errors.total_due[0] }}</small>
          </div>
          <div class="col-12">
            <label class="form-label text-light">Address</label>
            <textarea v-model="form.address" class="form-control custom-input" rows="3"></textarea>
            <small class="text-danger" v-if="clientStore.errors.address">{{ clientStore.errors.address[0] }}</small>
          </div>
          <div class="col-12 mt-4">
            <button type="submit" class="btn btn-primary px-4" :disabled="clientStore.loading">
              {{ clientStore.loading ? 'Saving...' : 'Save Client' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useClientStore } from '../../../store/clients';
import { useRouter } from 'vue-router';

const clientStore = useClientStore();
const router = useRouter();

const form = ref({
  name: '',
  phone: '',
  email: '',
  total_due: 0,
  address: ''
});

onMounted(() => {
  clientStore.errors = {};
});

const saveClient = async () => {
  const success = await clientStore.createClient(form.value);
  if (success) {
    router.push('/clients');
  }
};
</script>
