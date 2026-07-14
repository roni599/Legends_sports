<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Client List</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search name or phone..." style="width: 200px;">
        <router-link to="/clients/create" class="btn btn-primary btn-sm">
          + Add New Client
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
              <td>
                <div class="d-flex align-items-center gap-2">
                  {{ client.phone }}
                  <a :href="'https://wa.me/' + client.phone" target="_blank" class="btn btn-sm btn-success p-1 lh-1" title="WhatsApp">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg>
                  </a>
                  <a :href="'tel:' + client.phone" class="btn btn-sm btn-primary p-1 lh-1" title="Call">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/></svg>
                  </a>
                </div>
              </td>
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
import { ref, onMounted } from 'vue';
import { useClientStore } from '../../../store/clients';

const clientStore = useClientStore();
const searchQuery = ref('');
let searchTimeout = null;

onMounted(() => {
  clientStore.fetchClients();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    clientStore.fetchClients(1, searchQuery.value);
  }, 300);
};

const changePage = (page) => {
  clientStore.fetchClients(page, searchQuery.value);
};
</script>
