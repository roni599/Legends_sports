import { defineStore } from 'pinia';
import axios from 'axios';

export const useBookingStore = defineStore('bookings', {
  state: () => ({
    grounds: [],
    activeGrounds: [],
    pricePreview: null,
    priceError: null,
    loading: false,
    creating: false,
    error: null,
  }),

  actions: {
    async fetchGrounds() {
      try {
        const { data } = await axios.get('/api/grounds?all=true');
        this.grounds = data;
        this.activeGrounds = data.filter(g => g.status === 'active');
      } catch (error) {
        console.error('Error fetching grounds', error);
      }
    },

    async calculatePrice(payload) {
      this.priceError = null;
      try {
        const { data } = await axios.post('/api/bookings/calculate-price', payload);
        this.pricePreview = data;
        return data;
      } catch (error) {
        this.pricePreview = null;
        this.priceError = error.response?.data?.errors?.time_slot?.[0] || 'Invalid time slot selected.';
        return null;
      }
    },

    async createBooking(payload) {
      this.creating = true;
      this.error = null;
      try {
        await axios.post('/api/bookings', payload);
        return true;
      } catch (error) {
        this.error = error.response?.data?.errors?.time_slot?.[0] ||
                     error.response?.data?.message ||
                     'Failed to create booking';
        return false;
      } finally {
        this.creating = false;
      }
    },

    resetPrice() {
      this.pricePreview = null;
      this.priceError = null;
    },
  },
});
