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
                <button class="neon-toggle" :class="{ active: rule.status === 'active' }"
                  @click="pricingStore.toggleStatus(rule.id)">
                  <span class="neon-track">
                    <span class="neon-thumb"></span>
                  </span>
                </button>
              </td>
              <td>
                <router-link :to="`/grounds/pricing/${rule.id}/edit`" class="btn btn-sm btn-outline-info me-2">Edit</router-link>
                <button @click="pricingStore.deleteRule(rule.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="d-flex justify-content-between align-items-center p-3 mt-2">
        <div class="text-light small mb-2 mb-md-0">
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

<style scoped>
.neon-toggle {
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}
.neon-track {
  display: block;
  width: 52px;
  height: 28px;
  border-radius: 14px;
  background: #1a1d23;
  border: 2px solid #2a2d35;
  position: relative;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.neon-toggle.active .neon-track {
  background: #0d2818;
  border-color: #00e676;
  box-shadow: 0 0 10px rgba(0, 230, 118, 0.3), 0 0 25px rgba(0, 230, 118, 0.15), inset 0 0 10px rgba(0, 230, 118, 0.1);
}
.neon-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #4a4d55;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 4px rgba(0,0,0,0.4);
}
.neon-toggle.active .neon-thumb {
  transform: translateX(24px);
  background: #00e676;
  box-shadow: 0 0 8px #00e676, 0 0 20px rgba(0, 230, 118, 0.5), 0 2px 6px rgba(0,0,0,0.3);
}
.neon-toggle:hover .neon-track {
  border-color: #3a3d45;
}
.neon-toggle.active:hover .neon-track {
  border-color: #00e676;
  box-shadow: 0 0 14px rgba(0, 230, 118, 0.4), 0 0 35px rgba(0, 230, 118, 0.2), inset 0 0 12px rgba(0, 230, 118, 0.15);
}
</style>
