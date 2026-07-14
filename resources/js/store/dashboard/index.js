import { defineStore } from 'pinia';

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    todayBookings: 12,
    totalRevenue: '15,400',
    totalDue: '3,200',
    occupancyRate: 78
  }),
  
  actions: {
    // Actions to fetch dashboard data from API will go here
  }
});
