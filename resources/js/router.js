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
        component: ClientList
      },
      {
        path: 'clients/create',
        name: 'ClientCreate',
        component: ClientCreate
      },
      {
        path: 'clients/:id/edit',
        name: 'ClientEdit',
        component: () => import('./modules/clients/views/Edit.vue')
      }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' });
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router;
