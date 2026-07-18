import { defineStore } from 'pinia';
import axios from 'axios';

export const useClientStore = defineStore('client', {
  state: () => ({
    clients: [],
    loading: false,
    errors: {},
    page: 1,
    total: 0,
    searchQuery: '',
    allClients: []
  }),
  
  actions: {
    async fetchClients(page = 1, search = null) {
      this.loading = true;
      this.page = page;
      if (search !== null) {
          this.searchQuery = search;
      }
      try {
        const url = this.searchQuery ? `/api/clients?page=${page}&search=${this.searchQuery}` : `/api/clients?page=${page}`;
        const response = await axios.get(url);
        this.clients = response.data.data;
        this.total = response.data.total;
      } catch (error) {
        console.error("Error fetching clients", error);
      } finally {
        this.loading = false;
      }
    },
    
    async createClient(data) {
      this.loading = true;
      this.errors = {};
      try {
        await axios.post('/api/clients', data);
        this.loading = false;
        return true;
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors;
        }
        this.loading = false;
        return false;
      }
    },
    
    async updateClient(id, data) {
      this.loading = true;
      this.errors = {};
      try {
        await axios.put(`/api/clients/${id}`, data);
        this.loading = false;
        return true;
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.errors = error.response.data.errors;
        }
        this.loading = false;
        return false;
      }
    },

    async toggleStatus(id) {
      try {
        const { data } = await axios.patch(`/api/clients/${id}/toggle-status`);
        const client = this.clients.find(c => c.id === id);
        if (client) client.status = data.status;
        return data.status;
      } catch (error) {
        console.error('Error toggling status', error);
        return null;
      }
    },

    async fetchAllClients() {
      try {
        const { data } = await axios.get('/api/clients?dropdown=true');
        this.allClients = data;
      } catch (error) {
        console.error('Error fetching all clients', error);
      }
    },

    async deleteClient(id) {
      if (!confirm('Are you sure you want to delete this client?')) return false;
      
      this.loading = true;
      try {
        await axios.delete(`/api/clients/${id}`);
        if (this.clients.length === 1 && this.page > 1) {
            this.page--;
        }
        await this.fetchClients(this.page, this.searchQuery); // Refresh list with current search
        return true;
      } catch (error) {
        console.error("Error deleting client", error);
        this.loading = false;
        return false;
      }
    }
  }
});
