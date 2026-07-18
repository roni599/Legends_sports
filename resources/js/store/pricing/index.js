import { defineStore } from 'pinia';
import axios from 'axios';

export const usePricingStore = defineStore('pricing', {
  state: () => ({
    rules: [],
    loading: false,
    errors: {},
    page: 1,
    total: 0,
    searchQuery: ''
  }),
  
  actions: {
    async fetchRules(page = 1, search = null) {
      this.loading = true;
      this.page = page;
      if (search !== null) {
          this.searchQuery = search;
      }
      try {
        const url = this.searchQuery ? `/api/pricing-rules?page=${page}&search=${this.searchQuery}` : `/api/pricing-rules?page=${page}`;
        const response = await axios.get(url);
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

    async toggleStatus(id) {
      try {
        const response = await axios.patch(`/api/pricing-rules/${id}/toggle-status`);
        const updated = response.data;
        const rule = this.rules.find(r => r.id === id);
        if (rule) rule.status = updated.status;
        return true;
      } catch (error) {
        console.error("Error toggling status", error);
        return false;
      }
    },

    async deleteRule(id) {
      if (!confirm('Are you sure you want to delete this pricing rule?')) return false;
      
      this.loading = true;
      try {
        await axios.delete(`/api/pricing-rules/${id}`);
        if (this.rules.length === 1 && this.page > 1) {
            this.page--;
        }
        await this.fetchRules(this.page, this.searchQuery);
        return true;
      } catch (error) {
        console.error("Error deleting pricing rule", error);
        this.loading = false;
        return false;
      }
    }
  }
});
