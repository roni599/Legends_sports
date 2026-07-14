<template>
  <div class="content-card">
    <div class="content-header">
      <h3 class="fs-5 m-0 text-light">Pricing Rules List</h3>
      <router-link to="/grounds/pricing/create" class="btn btn-primary btn-sm">
        + Add New Rule
      </router-link>
    </div>
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Time</th>
              <th>Modifier (৳)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="pricingStore.loading">
              <td colspan="6" class="text-center py-4">Loading rules...</td>
            </tr>
            <tr v-else-if="pricingStore.rules.length === 0">
              <td colspan="6" class="text-center py-4">No rules found</td>
            </tr>
            <tr v-else v-for="rule in pricingStore.rules" :key="rule.id">
              <td>#{{ rule.id }}</td>
              <td>{{ rule.name }} <br><small class="text-muted" v-if="rule.ground">({{ rule.ground.name }})</small></td>
              <td>
                <span class="badge bg-secondary">{{ rule.type.replace('_', ' ').toUpperCase() }}</span>
              </td>
              <td>{{ rule.start_time || 'Any' }} - {{ rule.end_time || 'Any' }}</td>
              <td class="text-warning fw-bold">{{ rule.price_modifier > 0 ? '+' : '' }}{{ rule.price_modifier }}</td>
              <td>
                <router-link :to="`/grounds/pricing/${rule.id}/edit`" class="btn btn-sm btn-outline-info me-2">Edit</router-link>
                <button @click="pricingStore.deleteRule(rule.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-secondary small">Showing {{ pricingStore.rules.length }} entries of {{ pricingStore.total }} entries</span>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="pricingStore.page === 1" @click="changePage(pricingStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" @click="changePage(pricingStore.page + 1)">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { usePricingStore } from '../../../../store/pricing';

const pricingStore = usePricingStore();

onMounted(() => {
  pricingStore.fetchRules();
});

const changePage = (page) => {
  pricingStore.fetchRules(page);
};
</script>
