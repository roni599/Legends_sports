import { defineStore } from 'pinia';
import axios from 'axios';

export const usePurchaseStore = defineStore('purchases', {
  state: () => ({
    purchases: [],
    loading: false,
    error: null,
    total: 0,
    page: 1,
    perPage: 15
  }),

  actions: {
    async fetchPurchases(page = 1, supplierId = null) {
      this.loading = true;
      try {
        let url = `/api/purchases?page=${page}`;
        if (supplierId) url += `&supplier_id=${supplierId}`;
        
        const response = await axios.get(url);
        this.purchases = response.data.data;
        this.total = response.data.total;
        this.page = response.data.current_page;
        this.perPage = response.data.per_page;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch purchases';
      } finally {
        this.loading = false;
      }
    },

    async addPurchase(purchaseData) {
      this.loading = true;
      try {
        const response = await axios.post('/api/purchases', purchaseData);
        this.purchases.unshift(response.data);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});
