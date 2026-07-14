import { defineStore } from 'pinia';
import axios from 'axios';

export const useClientStore = defineStore('client', {
  state: () => ({
    clients: [],
    loading: false,
    errors: {},
    page: 1,
    total: 0
  }),
  
  actions: {
    async fetchClients(page = 1) {
      this.loading = true;
      this.page = page;
      try {
        const response = await axios.get(`/api/clients?page=${page}`);
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

    async deleteClient(id) {
      if (!confirm('Are you sure you want to delete this client?')) return false;
      
      this.loading = true;
      try {
        await axios.delete(`/api/clients/${id}`);
        await this.fetchClients(this.page); // Refresh list
        return true;
      } catch (error) {
        console.error("Error deleting client", error);
        this.loading = false;
        return false;
      }
    }
  }
});
