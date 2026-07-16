<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Users List</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search name, email or phone..." style="width: 250px;">
        <button class="btn btn-primary btn-sm" @click="openModal(null)">
          + Add New User
        </button>
      </div>
    </div>

    <!-- Users Table -->
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>SL</th>
              <th>Name</th>
              <th>Email</th>
              <th>Mobile Number</th>
              <th>User Type</th>
              <th>Business Branch</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
              <tr v-if="userStore.loading">
                <td colspan="8" class="text-center py-4">
                  <span class="spinner-border spinner-border-sm text-primary"></span> Loading...
                </td>
              </tr>
              <tr v-else-if="userStore.users.length === 0">
                <td colspan="8" class="text-center py-4">No users found</td>
              </tr>
              <tr v-for="(user, index) in userStore.users" :key="user.id">
                <td>{{ index + 1 }}</td>
                <td class="fw-bold">{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>{{ user.phone || '-' }}</td>
                <td>
                  <span class="badge bg-info text-dark" v-if="user.roles && user.roles.length">
                    {{ user.roles[0].name }}
                  </span>
                  <span v-else class="text-muted">None</span>
                </td>
                <td>{{ user.business_branch || '-' }}</td>
                <td>
                  <span class="badge" :class="user.is_active ? 'bg-success' : 'bg-danger'">
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-2" @click="openModal(user)">
                    Edit
                  </button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteUser(user)">
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">{{ isEditing ? 'Edit User' : 'Create New User' }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeUserModal"></button>
          </div>
          <form @submit.prevent="submitForm">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Name</label>
                <input type="text" class="form-control text-dark" v-model="form.name" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" class="form-control text-dark" v-model="form.email" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Mobile Number</label>
                <input type="text" class="form-control text-dark" v-model="form.phone">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Business Branch</label>
                <input type="text" class="form-control text-dark" v-model="form.business_branch">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">User Type (Role)</label>
                <VueMultiselect
                  v-model="form.roleObj"
                  :options="userStore.roles"
                  track-by="id"
                  label="name"
                  placeholder="Select Role"
                  :searchable="false"
                  :show-labels="false"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold">Password {{ isEditing ? '(Leave blank to keep unchanged)' : '' }}</label>
                <input type="password" class="form-control text-dark" v-model="form.password" minlength="6" :required="!isEditing">
              </div>
              <div class="mb-3 form-check form-switch">
                <input class="form-check-input" type="checkbox" id="isActive" v-model="form.is_active">
                <label class="form-check-label" for="isActive">Status Active</label>
              </div>
            </div>
            <div class="modal-footer border-secondary">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                {{ isEditing ? 'Update User' : 'Save User' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useUserStore } from '../../../store/users';
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import Swal from 'sweetalert2';

const userStore = useUserStore();

const isEditing = ref(false);
const isSaving = ref(false);
const editingId = ref(null);
const searchQuery = ref('');
let searchTimeout = null;

const form = ref({
  name: '',
  email: '',
  phone: '',
  business_branch: '',
  roleObj: null,
  password: '',
  is_active: true
});

onMounted(() => {
  userStore.fetchUsers();
  userStore.fetchRoles();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    userStore.fetchUsers(searchQuery.value);
  }, 500);
};

const openModal = (user = null) => {
  if (user) {
    isEditing.value = true;
    editingId.value = user.id;
    form.value = {
      name: user.name,
      email: user.email,
      phone: user.phone || '',
      business_branch: user.business_branch || '',
      roleObj: user.roles && user.roles.length ? user.roles[0] : null,
      password: '',
      is_active: user.is_active === 1 || user.is_active === true
    };
  } else {
    isEditing.value = false;
    editingId.value = null;
    form.value = {
      name: '',
      email: '',
      phone: '',
      business_branch: '',
      roleObj: null,
      password: '',
      is_active: true
    };
  }
  
  if (window.bootstrap) {
    const modal = new window.bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
  }
};

const submitForm = async () => {
  if (!form.value.roleObj) {
    Swal.fire('Error', 'Please select a User Type (Role)', 'error');
    return;
  }
  
  isSaving.value = true;
  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      business_branch: form.value.business_branch,
      role_id: form.value.roleObj.id,
      is_active: form.value.is_active ? 1 : 0
    };
    
    if (form.value.password) {
      payload.password = form.value.password;
    }
    
    if (isEditing.value) {
      await userStore.updateUser(editingId.value, payload);
      Swal.fire('Updated!', 'User has been updated successfully.', 'success');
    } else {
      await userStore.createUser(payload);
      Swal.fire('Created!', 'User has been created successfully.', 'success');
    }
    
    document.getElementById('closeUserModal').click();
  } catch (error) {
    let msg = error.response?.data?.message || 'Something went wrong';
    if (error.response?.data?.errors) {
      msg = Object.values(error.response.data.errors).flat().join('\n');
    }
    Swal.fire('Error', msg, 'error');
  } finally {
    isSaving.value = false;
  }
};

const deleteUser = (user) => {
  Swal.fire({
    title: 'Are you sure?',
    text: `You are about to delete user: ${user.name}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await userStore.deleteUser(user.id);
        Swal.fire('Deleted!', 'User has been deleted.', 'success');
      } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Cannot delete this user', 'error');
      }
    }
  });
};
</script>
