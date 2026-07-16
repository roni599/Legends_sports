<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Pricing Rules List</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search rule, ground, type..." style="width: 240px;">
        <router-link to="/grounds/pricing/create" class="btn btn-primary btn-sm">
          + Add New Rule
        </router-link>
      </div>
    </div>
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>SL</th>
              <th>Name</th>
              <th>Type</th>
              <th>Time</th>
              <th>Modifier (৳)</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="pricingStore.loading">
              <td colspan="7" class="text-center py-4">Loading rules...</td>
            </tr>
            <tr v-else-if="pricingStore.rules.length === 0">
              <td colspan="7" class="text-center py-4">No rules found</td>
            </tr>
            <tr v-else v-for="(rule, index) in pricingStore.rules" :key="rule.id">
              <td>{{ index + 1 }}</td>
              <td>{{ rule.name }} <br><small class="text-muted" v-if="rule.ground">({{ rule.ground.name }})</small></td>
              <td>
                <span class="badge bg-secondary">{{ rule.type.replace('_', ' ').toUpperCase() }}</span>
              </td>
              <td>{{ rule.start_time || 'Any' }} - {{ rule.end_time || 'Any' }}</td>
              <td class="text-warning fw-bold">{{ rule.price_modifier > 0 ? '+' : '' }}{{ rule.price_modifier }}</td>
              <td>
                <span class="badge" :class="rule.status === 'active' ? 'bg-success' : 'bg-danger'">
                  {{ rule.status.toUpperCase() }}
                </span>
              </td>
              <td>
                <router-link :to="`/grounds/pricing/${rule.id}/edit`" class="btn btn-sm btn-outline-info me-2">Edit</router-link>
                <button @click="pricingStore.deleteRule(rule.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
        <div class="text-secondary small mb-2 mb-md-0">
          Showing {{ pricingStore.rules.length === 0 ? 0 : ((pricingStore.page - 1) * 10) + 1 }} to {{ Math.min(pricingStore.page * 10, pricingStore.total) }} of {{ pricingStore.total }} entries
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="pricingStore.page === 1" @click="changePage(pricingStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="pricingStore.page * 10 >= pricingStore.total" @click="changePage(pricingStore.page + 1)">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePricingStore } from '../../../../store/pricing';

const pricingStore = usePricingStore();
const searchQuery = ref('');
let searchTimeout = null;

onMounted(() => {
  pricingStore.fetchRules();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    pricingStore.fetchRules(1, searchQuery.value);
  }, 300);
};

const changePage = (page) => {
  pricingStore.fetchRules(page, searchQuery.value);
};
</script>
