import { defineStore } from 'pinia';
import axios from 'axios';

export const usePricingStore = defineStore('pricing', {
  state: () => ({
    rules: [],
    loading: false,
    errors: {},
    page: 1,
    total: 0
  }),
  
  actions: {
    async fetchRules(page = 1) {
      this.loading = true;
      this.page = page;
      try {
        const response = await axios.get(`/api/pricing-rules?page=${page}`);
        this.rules = response.data.data;
        this.total = response.data.total;
      } catch (error) {
        console.error("Error fetching pricing rules", error);
      } finally {
        this.loading = false;
      }
    },
    
    async createRule(data) {
      this.loading = true;
      this.errors = {};
      try {
        await axios.post('/api/pricing-rules', data);
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
    
    async updateRule(id, data) {
      this.loading = true;
      this.errors = {};
      try {
        await axios.put(`/api/pricing-rules/${id}`, data);
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

    async deleteRule(id) {
      if (!confirm('Are you sure you want to delete this pricing rule?')) return false;
      
      this.loading = true;
      try {
        await axios.delete(`/api/pricing-rules/${id}`);
        await this.fetchRules(this.page);
        return true;
      } catch (error) {
        console.error("Error deleting pricing rule", error);
        this.loading = false;
        return false;
      }
    }
  }
});
