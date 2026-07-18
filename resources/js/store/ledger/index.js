import { defineStore } from 'pinia';
import axios from 'axios';

export const useLedgerStore = defineStore('ledger', {
  state: () => ({
    client: null,
    bookings: [],
    payments: [],
    summary: {
      total_bookings: 0,
      total_booked: 0,
      total_plays: 0,
      total_paid: 0,
      total_due: 0,
      total_advance: 0,
      total_booked_amount: 0,
      total_play_amount: 0,
    },
    loading: false,
    error: null
  }),

  actions: {
    async fetchLedger(clientId) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await axios.get(`/api/clients/${clientId}/ledger`);
        this.client = data.client;
        this.bookings = data.ledger;
        this.payments = data.payments;
        this.summary = data.summary;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load ledger.';
        console.error('Error fetching ledger', error);
      } finally {
        this.loading = false;
      }
    },

    reset() {
      this.client = null;
      this.bookings = [];
      this.payments = [];
      this.summary = {
      total_bookings: 0,
      total_booked: 0,
      total_plays: 0,
        total_paid: 0,
        total_due: 0,
        total_advance: 0,
        total_booked_amount: 0,
        total_play_amount: 0,
      };
      this.error = null;
    }
  }
});
