import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    hasRole: (state) => (role) => {
      if (!state.user || !state.user.roles) return false;
      return state.user.roles.some(r => r.slug === role);
    },
    hasPermission: (state) => (permission) => {
      if (!state.user || !state.user.roles) return false;
      // Admin/Super-admin bypass
      if (state.user.roles.some(r => ['admin', 'super-admin', 'super_admin'].includes(r.slug))) {
        return true;
      }
      return state.user.roles.some(r => 
        r.permissions && r.permissions.some(p => p.slug === permission)
      );
    }
  },
  
  actions: {
    async login(email, password) {
      await axios.get('/sanctum/csrf-cookie');
      const response = await axios.post('/api/login', { email, password });
      
      this.user = response.data.user;
      this.token = response.data.token;
      localStorage.setItem('token', response.data.token);
      axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
    },
    
    async logout() {
      if (this.token) {
        try {
          await axios.post('/api/logout', {}, {
            headers: { Authorization: `Bearer ${this.token}` }
          });
        } catch (e) {}
      }
      this.user = null;
      this.token = null;
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
    },

    async fetchUser() {
      if (!this.token) return;
      try {
        const response = await axios.get('/api/user', {
          headers: { Authorization: `Bearer ${this.token}` }
        });
        this.user = response.data;
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
      } catch (e) {
        this.logout();
      }
    }
  }
});
