<template>
  <div class="content-card">
    <div class="content-header">
      <h3 class="fs-5 m-0 text-light">Edit Ground</h3>
      <router-link to="/grounds" class="btn btn-outline-light btn-sm">
        Back to List
      </router-link>
    </div>
    <div class="p-4">
      <div v-if="groundStore.loading && !form.id" class="text-center text-light">
        Loading...
      </div>
      <form v-else @submit.prevent="updateGround">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label text-light">Ground Name *</label>
            <input type="text" v-model="form.name" class="form-control custom-input" required>
            <small class="text-danger" v-if="groundStore.errors.name">{{ groundStore.errors.name[0] }}</small>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Status *</label>
            <select v-model="form.status" class="form-select custom-input" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Base Price Per Hour (৳) *</label>
            <input type="number" step="0.01" v-model="form.base_price_per_hour" class="form-control custom-input" required>
            <small class="text-danger" v-if="groundStore.errors.base_price_per_hour">{{ groundStore.errors.base_price_per_hour[0] }}</small>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Location</label>
            <input type="text" v-model="form.location" class="form-control custom-input">
            <small class="text-danger" v-if="groundStore.errors.location">{{ groundStore.errors.location[0] }}</small>
          </div>
          <div class="col-12">
            <label class="form-label text-light">Description (Rich Text)</label>
            <div class="bg-white rounded">
              <QuillEditor v-model:content="form.description" contentType="html" theme="snow" style="min-height: 150px; color: black;" />
            </div>
          </div>
          <div class="col-12 mt-4">
            <button type="submit" class="btn btn-primary px-4" :disabled="groundStore.loading">
              {{ groundStore.loading ? 'Updating...' : 'Update Ground' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useGroundStore } from '../../../store/grounds';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const groundStore = useGroundStore();
const router = useRouter();
const route = useRoute();

const form = ref({
  id: '',
  name: '',
  location: '',
  description: '',
  base_price_per_hour: 0,
  status: 'active'
});

onMounted(async () => {
  groundStore.errors = {};
  groundStore.loading = true;
  try {
    const response = await axios.get(`/api/grounds/${route.params.id}`);
    const ground = response.data;
    form.value = response.data;
  } catch (error) {
    console.error("Error loading ground details", error);
    router.push('/grounds');
  } finally {
    groundStore.loading = false;
  }
});

const updateGround = async () => {
  const success = await groundStore.updateGround(form.value.id, form.value);
  if (success) {
    router.push('/grounds');
  }
};
</script>
