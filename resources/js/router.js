import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './store/auth';

import AdminLayout from './modules/core/layouts/AdminLayout.vue';
import Login from './modules/auth/views/Login.vue';
import Dashboard from './modules/dashboard/views/Index.vue';
import ClientList from './modules/clients/views/List.vue';
import ClientCreate from './modules/clients/views/Create.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guest: true }
  },
  {
    path: '/',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: Dashboard
      },
      {
        path: 'clients',
        name: 'ClientList',
        component: ClientList,
        meta: { permission: 'manage_bookings' }
      },
      {
        path: 'clients/create',
        name: 'ClientCreate',
        component: ClientCreate,
        meta: { permission: 'manage_bookings' }
      },
      {
        path: 'clients/:id/edit',
        name: 'ClientEdit',
        component: () => import('./modules/clients/views/Edit.vue'),
        meta: { permission: 'manage_bookings' }
      },
      {
        path: 'grounds',
        name: 'GroundList',
        component: () => import('./modules/grounds/views/List.vue'),
        meta: { permission: 'manage_grounds' }
      },
      {
        path: 'grounds/create',
        name: 'GroundCreate',
        component: () => import('./modules/grounds/views/Create.vue'),
        meta: { permission: 'manage_grounds' }
      },
      {
        path: 'grounds/:id/edit',
        name: 'GroundEdit',
        component: () => import('./modules/grounds/views/Edit.vue'),
        meta: { permission: 'manage_grounds' }
      },
      {
        path: 'grounds/pricing',
        name: 'PricingRuleList',
        component: () => import('./modules/grounds/views/pricing/List.vue'),
        meta: { permission: 'manage_grounds' }
      },
      {
        path: 'grounds/pricing/create',
        name: 'PricingRuleCreate',
        component: () => import('./modules/grounds/views/pricing/Create.vue'),
        meta: { permission: 'manage_grounds' }
      },
      {
        path: 'grounds/pricing/:id/edit',
        name: 'PricingRuleEdit',
        component: () => import('./modules/grounds/views/pricing/Edit.vue'),
        meta: { permission: 'manage_grounds' }
      },
      {
        path: 'bookings',
        name: 'BookingList',
        component: () => import('./modules/bookings/views/List.vue'),
        meta: { permission: 'view_bookings' }
      },
      {
        path: 'calendar',
        name: 'BookingCalendar',
        component: () => import('./modules/bookings/views/Calendar.vue'),
        meta: { permission: 'view_bookings' }
      },
      {
        path: 'settings/roles',
        name: 'SettingsRoles',
        component: () => import('./modules/settings/views/UserPermissions.vue'),
        meta: { permission: 'view_users' }
      },
      {
        path: 'expenses',
        name: 'ExpenseList',
        component: () => import('./modules/expenses/views/List.vue'),
        meta: { permission: 'view_bookings' }
      },
      {
        path: 'suppliers',
        name: 'SupplierList',
        component: () => import('./modules/suppliers/views/List.vue'),
        meta: { permission: 'view_bookings' }
      },
      {
        path: 'pos',
        name: 'POS',
        component: () => import('./modules/pos/views/Index.vue'),
        meta: { permission: 'view_bookings' }
      },
      {
        path: 'products',
        name: 'ProductList',
        component: () => import('./modules/pos/views/Products.vue'),
        meta: { permission: 'view_bookings' }
      },
      {
        path: 'purchases',
        name: 'PurchaseList',
        component: () => import('./modules/purchases/views/List.vue'),
        meta: { permission: 'view_bookings' }
      },
      {
        path: 'month-closing',
        name: 'MonthClosing',
        component: () => import('./modules/accounting/views/MonthClosing.vue'),
        meta: { permission: 'view_bookings' }
      }
    ]
  },
  {
    path: '/print-invoice/:id',
    name: 'PrintInvoice',
    component: () => import('./modules/bookings/views/PrintInvoice.vue'),
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'Login' });
  } 
  
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'Dashboard' });
  } 
  
  if (authStore.isAuthenticated && !authStore.user) {
    await authStore.fetchUser();
  }

  if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
    return next({ name: 'Dashboard' });
  } 
  
  next();
});

export default router;
