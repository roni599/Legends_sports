import { defineStore } from 'pinia';
import axios from 'axios';

export const useGroundStore = defineStore('ground', {
  state: () => ({
    grounds: [],
    loading: false,
    errors: {},
    page: 1,
    total: 0
  }),
  
  actions: {
    async fetchGrounds(page = 1, search = '') {
      this.loading = true;
      this.page = page;
      try {
        const url = search ? `/api/grounds?page=${page}&search=${search}` : `/api/grounds?page=${page}`;
        const response = await axios.get(url);
        this.grounds = response.data.data;
        this.total = response.data.total;
      } catch (error) {
        console.error("Error fetching grounds", error);
      } finally {
        this.loading = false;
      }
    },

    async fetchAllActiveGrounds() {
      try {
        const response = await axios.get('/api/grounds?all=true');
        this.grounds = response.data;
      } catch (error) {
        console.error("Error fetching all grounds", error);
      }
    },
    
    async createGround(data) {
      this.loading = true;
      this.errors = {};
      try {
        await axios.post('/api/grounds', data);
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
    
    async updateGround(id, data) {
      this.loading = true;
      this.errors = {};
      try {
        await axios.put(`/api/grounds/${id}`, data);
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

    async deleteGround(id) {
      if (!confirm('Are you sure you want to delete this ground?')) return false;
      
      this.loading = true;
      try {
        await axios.delete(`/api/grounds/${id}`);
        await this.fetchGrounds(this.page);
        return true;
      } catch (error) {
        console.error("Error deleting ground", error);
        this.loading = false;
        return false;
      }
    }
  }
});
