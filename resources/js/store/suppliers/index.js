import { defineStore } from 'pinia';
import axios from 'axios';

export const useSupplierStore = defineStore('suppliers', {
  state: () => ({
    suppliers: [],
    loading: false,
    error: null,
    total: 0,
    page: 1,
    perPage: 15
  }),

  actions: {
    async fetchSuppliers(page = 1, search = '') {
      this.loading = true;
      try {
        const response = await axios.get(`/api/suppliers?page=${page}&search=${search}`);
        this.suppliers = response.data.data;
        this.total = response.data.total;
        this.page = response.data.current_page;
        this.perPage = response.data.per_page;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch suppliers';
      } finally {
        this.loading = false;
      }
    },

    async addSupplier(supplierData) {
      this.loading = true;
      try {
        const response = await axios.post('/api/suppliers', supplierData);
        this.suppliers.unshift(response.data);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateSupplier(id, supplierData) {
      this.loading = true;
      try {
        const response = await axios.put(`/api/suppliers/${id}`, supplierData);
        const index = this.suppliers.findIndex(s => s.id === id);
        if (index !== -1) {
          this.suppliers[index] = response.data;
        }
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteSupplier(id) {
      if (!confirm('Are you sure you want to delete this supplier?')) return;
      try {
        await axios.delete(`/api/suppliers/${id}`);
        this.suppliers = this.suppliers.filter(s => s.id !== id);
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to delete supplier');
      }
    },

    async paySupplier(id, amount) {
      this.loading = true;
      try {
        const response = await axios.post(`/api/suppliers/${id}/pay`, { amount });
        const index = this.suppliers.findIndex(s => s.id === id);
        if (index !== -1) {
          this.suppliers[index] = response.data.supplier;
        }
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async receiveRefund(id, amount) {
      this.loading = true;
      try {
        const response = await axios.post(`/api/suppliers/${id}/refund`, { amount });
        const index = this.suppliers.findIndex(s => s.id === id);
        if (index !== -1) {
          this.suppliers[index] = response.data.supplier;
        }
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchLedger(id) {
      try {
        const response = await axios.get(`/api/suppliers/${id}/ledger`);
        return response.data.ledger;
      } catch (error) {
        throw error;
      }
    }
  }
});
