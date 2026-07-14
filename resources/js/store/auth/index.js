import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    hasRole: (state) => (role) => {
      return state.user?.roles?.includes(role) || false;
    }
  },
  
  actions: {
    setAuthData(user, token) {
      this.user = user;
      this.token = token;
      localStorage.setItem('token', token);
    },
    
    logout() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('token');
    }
  }
});
