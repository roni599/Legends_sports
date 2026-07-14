<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Ground List</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search name or location..." style="width: 220px;">
        <router-link to="/grounds/create" class="btn btn-primary btn-sm">
          + Add New Ground
        </router-link>
      </div>
    </div>
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Location</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="groundStore.loading">
              <td colspan="5" class="text-center py-4">Loading grounds...</td>
            </tr>
            <tr v-else-if="groundStore.grounds.length === 0">
              <td colspan="5" class="text-center py-4">No grounds found</td>
            </tr>
            <tr v-else v-for="ground in groundStore.grounds" :key="ground.id">
              <td>#{{ ground.id }}</td>
              <td>{{ ground.name }}</td>
              <td>{{ ground.location || '-' }}</td>
              <td>
                <span class="badge" :class="{
                  'bg-success': ground.status === 'active',
                  'bg-danger': ground.status === 'inactive',
                  'bg-warning text-dark': ground.status === 'maintenance'
                }">{{ ground.status.toUpperCase() }}</span>
              </td>
              <td>
                <router-link :to="`/grounds/${ground.id}/edit`" class="btn btn-sm btn-outline-info me-2">Edit</router-link>
                <button @click="groundStore.deleteGround(ground.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-secondary small">Showing {{ groundStore.grounds.length }} entries of {{ groundStore.total }} entries</span>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="groundStore.page === 1" @click="changePage(groundStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="groundStore.page * 10 >= groundStore.total" @click="changePage(groundStore.page + 1)">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useGroundStore } from '../../../store/grounds';

const groundStore = useGroundStore();
const searchQuery = ref('');
let searchTimeout = null;

onMounted(() => {
  groundStore.fetchGrounds();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    groundStore.fetchGrounds(1, searchQuery.value);
  }, 300);
};

const changePage = (page) => {
  groundStore.fetchGrounds(page, searchQuery.value);
};
</script>
