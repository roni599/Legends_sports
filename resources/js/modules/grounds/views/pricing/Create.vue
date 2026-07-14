<template>
  <div class="content-card">
    <div class="content-header">
      <h3 class="fs-5 m-0 text-light">Add New Pricing Rule</h3>
      <router-link to="/grounds/pricing" class="btn btn-outline-light btn-sm">
        Back to List
      </router-link>
    </div>
    <div class="p-4">
      <form @submit.prevent="saveRule">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label text-light">Rule Name *</label>
            <input type="text" v-model="form.name" class="form-control custom-input" required placeholder="e.g. Friday Weekend Peak">
          </div>
          <div class="col-md-4">
            <label class="form-label text-light">Ground (Optional)</label>
            <select v-model="form.ground_id" class="form-select custom-input">
              <option :value="null">All Grounds</option>
              <option v-for="ground in groundStore.grounds" :key="ground.id" :value="ground.id">{{ ground.name }}</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label text-light">Type *</label>
            <select v-model="form.type" class="form-select custom-input" required>
              <option value="peak_hour">Peak Hour</option>
              <option value="weekend">Weekend</option>
              <option value="tournament">Tournament</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Start Time</label>
            <input type="time" v-model="form.start_time" class="form-control custom-input">
            <small class="text-danger" v-if="pricingStore.errors.start_time">{{ pricingStore.errors.start_time[0] }}</small>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">End Time</label>
            <input type="time" v-model="form.end_time" class="form-control custom-input">
            <small class="text-danger" v-if="pricingStore.errors.end_time">{{ pricingStore.errors.end_time[0] }}</small>
          </div>
          <div class="col-md-6">
            <label class="form-label text-light">Price Modifier (৳) *</label>
            <input type="number" step="0.01" v-model="form.price_modifier" class="form-control custom-input" required>
            <small class="text-danger d-block" v-if="pricingStore.errors.price_modifier">{{ pricingStore.errors.price_modifier[0] }}</small>
            <small class="text-muted">Use negative value for discount</small>
          </div>
          <div class="col-12 mt-4">
            <button type="submit" class="btn btn-primary px-4" :disabled="pricingStore.loading">
              {{ pricingStore.loading ? 'Saving...' : 'Save Rule' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePricingStore } from '../../../../store/pricing';
import { useGroundStore } from '../../../../store/grounds';
import { useRouter } from 'vue-router';

const pricingStore = usePricingStore();
const groundStore = useGroundStore();
const router = useRouter();

const form = ref({
  name: '',
  ground_id: null,
  type: 'peak_hour',
  start_time: '',
  end_time: '',
  price_modifier: 0
});

onMounted(() => {
  groundStore.fetchAllActiveGrounds(); // load all active grounds for dropdown
});

const saveRule = async () => {
  const success = await pricingStore.createRule(form.value);
  if (success) {
    router.push('/grounds/pricing');
  }
};
</script>
