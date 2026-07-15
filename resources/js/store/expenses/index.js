import { defineStore } from 'pinia';
import axios from 'axios';

export const useExpenseStore = defineStore('expenses', {
  state: () => ({
    expenses: [],
    categories: [],
    loading: false,
    error: null,
    total: 0,
    page: 1,
    perPage: 15
  }),

  actions: {
    async fetchCategories() {
      this.loading = true;
      try {
        const response = await axios.get('/api/expense-categories?all=true');
        this.categories = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch categories';
      } finally {
        this.loading = false;
      }
    },

    async fetchExpenses(page = 1, search = '') {
      this.loading = true;
      try {
        const response = await axios.get(`/api/expenses?page=${page}&search=${search}`);
        this.expenses = response.data.data;
        this.total = response.data.total;
        this.page = response.data.current_page;
        this.perPage = response.data.per_page;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch expenses';
      } finally {
        this.loading = false;
      }
    },

    async addCategory(name) {
      this.loading = true;
      try {
        const response = await axios.post('/api/expense-categories', { name });
        this.categories.push(response.data);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteCategory(id) {
      if (!confirm('Are you sure you want to delete this category?')) return;
      try {
        await axios.delete(`/api/expense-categories/${id}`);
        this.categories = this.categories.filter(c => c.id !== id);
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to delete category');
      }
    },

    async addExpense(expenseData) {
      this.loading = true;
      try {
        const response = await axios.post('/api/expenses', expenseData);
        this.expenses.unshift(response.data);
        return response.data;
      } catch (error) {
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteExpense(id) {
      if (!confirm('Are you sure you want to delete this expense? Note: This will not automatically adjust your cash drawer in the dashboard since the outgoing payment log remains.')) return;
      try {
        await axios.delete(`/api/expenses/${id}`);
        this.expenses = this.expenses.filter(e => e.id !== id);
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to delete expense');
      }
    }
  }
});
