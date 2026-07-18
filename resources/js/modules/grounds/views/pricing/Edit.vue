<template>
  <div class="content-card">
    <div class="content-header">
      <h3 class="fs-5 m-0 text-light">Edit Pricing Rule</h3>
      <router-link to="/grounds/pricing" class="btn btn-outline-light btn-sm">
        Back to List
      </router-link>
    </div>
    <div class="p-4">
      <div v-if="pricingStore.loading && !form.id" class="text-center text-light">
        Loading...
      </div>
      <form v-else @submit.prevent="updateRule">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label text-light">Rule Name *</label>
            <input type="text" v-model="form.name" class="form-control custom-input" required>
            <small class="text-danger" v-if="pricingStore.errors.name">{{ pricingStore.errors.name[0] }}</small>
          </div>
          <div class="col-md-4">
            <label class="form-label text-light">Ground (Optional)</label>
            <VueMultiselect
              v-model="form.groundObj"
              :options="groundStore.grounds"
              track-by="id"
              label="name"
              placeholder="All Grounds"
              :searchable="true"
              :close-on-select="true"
              :show-labels="false"
            />
            <small class="text-danger" v-if="pricingStore.errors.ground_id">{{ pricingStore.errors.ground_id[0] }}</small>
          </div>
          <div class="col-md-4">
            <label class="form-label text-light">Type *</label>
            <select v-model="form.type" class="form-select custom-input" required>
              <option value="peak_hour">Peak Hour</option>
              <option value="weekend">Weekend</option>
              <option value="tournament">Tournament</option>
            </select>
            <small class="text-danger" v-if="pricingStore.errors.type">{{ pricingStore.errors.type[0] }}</small>
          </div>
          <div class="col-md-4">
            <label class="form-label text-light">Status *</label>
            <select v-model="form.status" class="form-select custom-input" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <small class="text-danger" v-if="pricingStore.errors.status">{{ pricingStore.errors.status[0] }}</small>
          </div>
          <div class="col-md-4">
            <label class="form-label text-light">Start Time</label>
            <input type="time" v-model="form.start_time" class="form-control custom-input">
            <small class="text-danger" v-if="pricingStore.errors.start_time">{{ pricingStore.errors.start_time[0] }}</small>
          </div>
          <div class="col-md-4">
            <label class="form-label text-light">End Time</label>
            <input type="time" v-model="form.end_time" class="form-control custom-input">
            <small class="text-danger" v-if="pricingStore.errors.end_time">{{ pricingStore.errors.end_time[0] }}</small>
          </div>
          <div class="col-md-12">
            <label class="form-label text-light">Price Modifier (৳) *</label>
            <input type="number" step="0.01" v-model="form.price_modifier" class="form-control custom-input" required>
            <small class="text-danger d-block" v-if="pricingStore.errors.price_modifier">{{ pricingStore.errors.price_modifier[0] }}</small>
          </div>
          <div class="col-12 mt-4">
            <button type="submit" class="btn btn-primary px-4" :disabled="pricingStore.loading">
              {{ pricingStore.loading ? 'Updating...' : 'Update Rule' }}
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
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';

const pricingStore = usePricingStore();
const groundStore = useGroundStore();
const router = useRouter();
const route = useRoute();

const form = ref({
  id: '',
  name: '',
  groundObj: null,
  type: 'peak_hour',
  start_time: '',
  end_time: '',
  price_modifier: 0,
  status: 'active'
});

onMounted(async () => {
  pricingStore.errors = {};
  groundStore.fetchAllActiveGrounds(); // load all active grounds for dropdown
  pricingStore.loading = true;
  try {
    const response = await axios.get(`/api/pricing-rules/${route.params.id}`);
    form.value = {
      ...response.data,
      groundObj: groundStore.grounds.find(g => g.id === response.data.ground_id) || null
    };
    // Format times if they include seconds from DB (e.g. 18:00:00 -> 18:00)
    if(form.value.start_time) form.value.start_time = form.value.start_time.substring(0,5);
    if(form.value.end_time) form.value.end_time = form.value.end_time.substring(0,5);
  } catch (error) {
    console.error("Error loading pricing rule details", error);
    router.push('/grounds/pricing');
  } finally {
    pricingStore.loading = false;
  }
});

const updateRule = async () => {
  const payload = {
    ...form.value,
    ground_id: form.value.groundObj ? form.value.groundObj.id : null
  };
  const success = await pricingStore.updateRule(form.value.id, payload);
  if (success) {
    router.push('/grounds/pricing');
  }
};
</script>

<style scoped>
:deep(.multiselect) {
  background-color: #2a2a2a;
  border-color: #444;
}
:deep(.multiselect__single),
:deep(.multiselect__input) {
  background-color: #2a2a2a;
  color: #fff;
}
:deep(.multiselect__placeholder) {
  color: #ffffff;
}
:deep(.multiselect__content-wrapper) {
  background-color: #2a2a2a;
  border-color: #444;
}
:deep(.multiselect__option) {
  background-color: #2a2a2a;
  color: #fff;
}
:deep(.multiselect__option--highlight) {
  background-color: #3a3a3a;
  color: #fff;
}
:deep(.multiselect__option--selected) {
  background-color: #198754;
  color: #fff;
}
:deep(.multiselect__tags) {
  background-color: #2a2a2a;
  border-color: #444;
  color: #fff;
}
:deep(.multiselect__select) {
  background-color: #2a2a2a;
}
:deep(.multiselect__select::before) {
  border-color: #fff transparent transparent;
}
.custom-input {
  color: #fff !important;
}
select.custom-input {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 12px;
  padding-right: 32px;
}
select.custom-input option {
  background-color: #2a2a2a !important;
  color: #fff !important;
}
input[type="time"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
}
</style>
