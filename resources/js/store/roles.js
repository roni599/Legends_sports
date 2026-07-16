import { defineStore } from 'pinia';
import axios from 'axios';

export const useRoleStore = defineStore('roles', {
  state: () => ({
    roles: [],
    permissions: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchRoles() {
      this.loading = true;
      try {
        const response = await axios.get('/api/roles');
        this.roles = response.data;
      } catch (err) {
        this.error = 'Failed to fetch roles';
        console.error(err);
      } finally {
        this.loading = false;
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
    async fetchRole(id) {
      try {
        const response = await axios.get(`/api/roles/${id}`);
        return response.data;
      } catch (err) {
        throw err;
      }
    },
    async updateRolePermissions(id, permissions) {
      try {
        const response = await axios.put(`/api/roles/${id}`, { permissions });
        return response.data;
      } catch (err) {
        throw err;
      }
    }
  }
});
