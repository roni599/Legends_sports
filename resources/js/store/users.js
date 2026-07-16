import { defineStore } from 'pinia';
import axios from 'axios';

export const useUserStore = defineStore('users', {
  state: () => ({
    users: [],
    roles: [],
    permissions: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchUsers(search = '') {
      this.loading = true;
      try {
        const url = search ? `/api/users?search=${encodeURIComponent(search)}` : '/api/users';
        const response = await axios.get(url);
        this.users = response.data;
      } catch (err) {
        this.error = 'Failed to fetch users';
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
    async fetchRoles() {
      try {
        const response = await axios.get('/api/users/roles');
        this.roles = response.data;
      } catch (err) {
        console.error(err);
      }
    },
    async fetchPermissions() {
      try {
        const response = await axios.get('/api/users/permissions');
        this.permissions = response.data;
      } catch (err) {
        console.error(err);
      }
    },
    async createUser(payload) {
      try {
        const response = await axios.post('/api/users', payload);
        this.users.unshift(response.data);
        return response.data;
      } catch (err) {
        throw err;
      }
    },
    async updateUser(id, payload) {
      try {
        const response = await axios.put(`/api/users/${id}`, payload);
        const index = this.users.findIndex(u => u.id === id);
        if (index !== -1) {
          this.users[index] = response.data;
        }
        return response.data;
      } catch (err) {
        throw err;
      }
    },
    async deleteUser(id) {
      try {
        await axios.delete(`/api/users/${id}`);
        this.users = this.users.filter(u => u.id !== id);
      } catch (err) {
        throw err;
      }
    }
  }
});
