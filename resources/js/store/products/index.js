import { defineStore } from 'pinia';
import axios from 'axios';

export const useProductStore = defineStore('products', {
  state: () => ({
    products: [],
    loading: false,
    error: null,
    total: 0,
    page: 1,
    perPage: 15
  }),

  actions: {
    async fetchProducts(page = 1, search = '', activeOnly = false, all = false) {
      this.loading = true;
      try {
        let url = `/api/products?page=${page}&search=${search}`;
        if (activeOnly) url += '&active_only=true';
        if (all) url += '&all=true';
        
        const response = await axios.get(url);
        
        if (all) {
            this.products = response.data;
        } else {
            this.products = response.data.data;
            this.total = response.data.total;
            this.page = response.data.current_page;
            this.perPage = response.data.per_page;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch products';
      } finally {
        this.loading = false;
      }
    },

    async addProduct(productData) {
      this.loading = true;
      try {
        const response = await axios.post('/api/products', productData);
        this.products.unshift(response.data);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateProduct(id, productData) {
      this.loading = true;
      try {
        const response = await axios.put(`/api/products/${id}`, productData);
        const index = this.products.findIndex(p => p.id === id);
        if (index !== -1) {
          this.products[index] = response.data;
        }
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteProduct(id) {
      if (!confirm('Are you sure you want to delete this product?')) return;
      try {
        await axios.delete(`/api/products/${id}`);
        this.products = this.products.filter(p => p.id !== id);
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to delete product');
      }
    }
  }
});
