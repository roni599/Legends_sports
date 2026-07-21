import { defineStore } from 'pinia';
import axios from 'axios';
import Swal from 'sweetalert2';

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
      const result = await Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to delete this client?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        customClass: { popup: 'swal-dark' },
        background: '#1a1d20',
        color: '#fff'
      });

      if (!result.isConfirmed) return false;
      
      this.loading = true;
      try {
        await axios.delete(`/api/clients/${id}`);
        if (this.clients.length === 1 && this.page > 1) {
            this.page--;
        }
        await this.fetchClients(this.page, this.searchQuery); // Refresh list with current search
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Client deleted successfully!', showConfirmButton: false, timer: 3000 });
        return true;
      } catch (error) {
        console.error("Error deleting client", error);
        this.loading = false;
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: error.response?.data?.message || 'Failed to delete client', showConfirmButton: false, timer: 4000 });
        return false;
      }
    }
  }
});
