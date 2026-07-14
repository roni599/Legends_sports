<template>
  <div class="content-card">
    <div class="content-header">
      <h3 class="fs-5 m-0 text-light">Client List</h3>
      <router-link to="/clients/create" class="btn btn-primary btn-sm">
        + Add New Client
      </router-link>
    </div>
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Total Due (৳)</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="clientStore.loading">
              <td colspan="6" class="text-center py-4">Loading clients...</td>
            </tr>
            <tr v-else-if="clientStore.clients.length === 0">
              <td colspan="6" class="text-center py-4">No clients found</td>
            </tr>
            <tr v-else v-for="client in clientStore.clients" :key="client.id">
              <td>#{{ client.id }}</td>
              <td>{{ client.name }}</td>
              <td>{{ client.phone }}</td>
              <td>{{ client.email || '-' }}</td>
              <td :class="{'text-danger fw-bold': client.total_due > 0}">{{ client.total_due }}</td>
              <td>
                <router-link :to="`/clients/${client.id}/edit`" class="btn btn-sm btn-outline-info me-2">Edit</router-link>
                <button @click="clientStore.deleteClient(client.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination Design per user requirement -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-secondary small">Showing {{ clientStore.clients.length }} entries of {{ clientStore.total }} entries</span>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="clientStore.page === 1" @click="changePage(clientStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" @click="changePage(clientStore.page + 1)">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useClientStore } from '../../../store/clients';

const clientStore = useClientStore();

onMounted(() => {
  clientStore.fetchClients();
});

const changePage = (page) => {
  clientStore.fetchClients(page);
};
</script>
