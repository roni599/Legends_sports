import { defineStore } from 'pinia';
import axios from 'axios';

export const useAccountingStore = defineStore('accounting', {
  state: () => ({
    monthlyClosings: [],
    loading: false,
    error: null,
  }),

  actions: {
    async fetchMonthlyClosings() {
      this.loading = true;
      try {
        const response = await axios.get('/api/monthly-closings');
        this.monthlyClosings = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch closings';
      } finally {
        this.loading = false;
      }
    },

    async closeMonth(monthYear) {
      if (!confirm(`Are you sure you want to lock the month ${monthYear}? This action cannot be undone.`)) return;
      this.loading = true;
      try {
        const response = await axios.post('/api/monthly-closings', { month_year: monthYear });
        this.monthlyClosings.unshift(response.data);
        alert('Month successfully locked!');
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to close month');
      } finally {
        this.loading = false;
      }
    }
  }
});
