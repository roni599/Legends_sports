<template>
  <div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
      <div class="col">
        <h2 class="h3 mb-0 text-gray-800">Advanced RBAC & Permissions</h2>
        <p class="text-muted mb-0">Select a user to modify their custom role and direct permissions.</p>
      </div>
    </div>

    <!-- User Selection -->
    <div class="card shadow-sm mb-4 border-0">
      <div class="card-body bg-light rounded">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <label class="form-label fw-bold mb-0">Select User</label>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            + Create New User
          </button>
        </div>
        <VueMultiselect
          v-model="selectedUserObj"
          :options="users"
          :custom-label="userLabel"
          track-by="id"
          placeholder="-- Choose a user --"
          :searchable="true"
          :close-on-select="true"
          :show-labels="false"
          @update:modelValue="loadUserPermissions"
        />
      </div>
    </div>

    <!-- Permissions Editor (Visible only when user is selected) -->
    <div v-if="selectedUser" class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold text-primary">Permissions for {{ selectedUser.name }}</h5>
        <button class="btn btn-primary px-4" @click="savePermissions" :disabled="isSaving">
          <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
          Save Permissions
        </button>
      </div>
      <div class="card-body">
        
        <!-- Base Role Selection -->
        <div class="mb-5 p-3 border rounded bg-light">
          <label class="form-label fw-bold text-dark">Primary Role (Template)</label>
          <VueMultiselect
            v-model="selectedUserForm.roleObj"
            :options="roles"
            track-by="id"
            label="name"
            placeholder="Select Role"
            :searchable="false"
            :show-labels="false"
          />
          <small class="text-muted mt-2 d-block">Selecting a role applies its base permissions. You can add extra direct permissions below.</small>
        </div>

        <h6 class="fw-bold text-dark mb-4 border-bottom pb-2">Direct Segment Permissions (Overrides)</h6>

        <!-- Checkbox Segments -->
        <div class="row g-4">
          <div class="col-md-6 col-lg-4" v-for="(perms, groupName) in groupedPermissions" :key="groupName">
            <div class="card h-100 border-primary border-opacity-25 shadow-sm hover-shadow transition">
              <div class="card-header bg-primary bg-opacity-10 py-3">
                <div class="form-check form-switch d-flex align-items-center">
                  <input class="form-check-input me-3 shadow-none mt-0" 
                         style="width: 40px; height: 20px; cursor:pointer;" 
                         type="checkbox" 
                         :id="'group-' + groupName"
                         :checked="isGroupFullySelected(groupName)"
                         @change="toggleGroup(groupName, $event.target.checked)">
                  <label class="form-check-label fw-bold text-primary m-0" :for="'group-' + groupName" style="cursor:pointer; font-size: 1.1rem;">
                    {{ formatGroupName(groupName) }} Segment
                  </label>
                </div>
              </div>
              <div class="card-body">
                <div class="form-check mb-3 ms-2" v-for="permission in perms" :key="permission.id">
                  <input class="form-check-input" 
                         type="checkbox" 
                         :value="permission.id" 
                         v-model="selectedUserForm.permissions"
                         :id="'perm-' + permission.id">
                  <label class="form-check-label text-secondary" :for="'perm-' + permission.id">
                    {{ permission.name }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Create User Modal -->
  <div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Create New User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeCreateUserModal"></button>
        </div>
        <form @submit.prevent="submitCreateUser">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Name</label>
              <input type="text" class="form-control" v-model="createUserForm.name" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Email</label>
              <input type="email" class="form-control" v-model="createUserForm.email" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Password</label>
              <input type="password" class="form-control" v-model="createUserForm.password" minlength="6" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Initial Role</label>
              <VueMultiselect
                v-model="createUserForm.roleObj"
                :options="roles"
                track-by="id"
                label="name"
                placeholder="Select Role"
                :searchable="false"
                :show-labels="false"
                required
              />
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="isCreatingUser">
              <span v-if="isCreatingUser" class="spinner-border spinner-border-sm me-1"></span>
              Create User
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';

const users = ref([]);
const roles = ref([]);
const permissions = ref([]);
const selectedUserObj = ref(null);
const isSaving = ref(false);

const selectedUserId = computed(() => selectedUserObj.value?.id || '');

const userLabel = (user) => {
  return `${user.name} (${user.email}) - ${user.roles?.[0]?.name || 'No Role'}`;
};

const selectedUserForm = ref({
  roleObj: null,
  permissions: []
});

const isCreatingUser = ref(false);
const createUserForm = ref({
  name: '',
  email: '',
  password: '',
  roleObj: null
});

const selectedUser = computed(() => {
  return users.value.find(u => u.id === selectedUserId.value) || null;
});

// Group permissions by their prefix (e.g., manage_users -> users)
const groupedPermissions = computed(() => {
  const groups = {};
  permissions.value.forEach(p => {
    // Assuming slugs are like 'manage_users', 'create_booking', 'view_reports'
    let group = p.slug.split('_').pop(); 
    if (group === 'bookings') group = 'Booking';
    if (group === 'grounds') group = 'Ground & Pricing';
    if (group === 'users') group = 'User Management';
    
    if (!groups[group]) groups[group] = [];
    groups[group].push(p);
  });
  return groups;
});

const formatGroupName = (name) => {
  return name.charAt(0).toUpperCase() + name.slice(1);
};

const isGroupFullySelected = (groupName) => {
  const groupPerms = groupedPermissions.value[groupName];
  if (!groupPerms || groupPerms.length === 0) return false;
  return groupPerms.every(p => selectedUserForm.value.permissions.includes(p.id));
};

const toggleGroup = (groupName, isChecked) => {
  const groupPerms = groupedPermissions.value[groupName];
  if (isChecked) {
    groupPerms.forEach(p => {
      if (!selectedUserForm.value.permissions.includes(p.id)) {
        selectedUserForm.value.permissions.push(p.id);
      }
    });
  } else {
    selectedUserForm.value.permissions = selectedUserForm.value.permissions.filter(
      id => !groupPerms.some(p => p.id === id)
    );
  }
};

const fetchData = async () => {
  try {
    const [usersRes, rolesRes, permsRes] = await Promise.all([
      axios.get('/api/users'),
      axios.get('/api/users/roles'),
      axios.get('/api/users/permissions')
    ]);
    users.value = usersRes.data;
    roles.value = rolesRes.data;
    permissions.value = permsRes.data;
  } catch (error) {
    alert('Failed to load RBAC data.');
  }
};

const loadUserPermissions = () => {
  if (selectedUser.value) {
    const roleId = selectedUser.value.roles[0]?.id || null;
    selectedUserForm.value.roleObj = roles.value.find(r => r.id === roleId) || null;
    selectedUserForm.value.permissions = selectedUser.value.direct_permissions?.map(p => p.id) || [];
  }
};

const savePermissions = async () => {
  if (!selectedUser.value) return;
  isSaving.value = true;
  try {
    await axios.put(`/api/users/${selectedUser.value.id}`, {
      name: selectedUser.value.name,
      email: selectedUser.value.email,
      role_id: selectedUserForm.value.roleObj?.id || null,
      permissions: selectedUserForm.value.permissions
    });
    
    // Refresh user list
    await fetchData();
    loadUserPermissions();
    
    alert('Permissions updated successfully!');
  } catch (error) {
    if (error.response?.status === 403) {
      alert(error.response.data.message);
    } else {
      alert('Failed to save permissions.');
    }
  } finally {
    isSaving.value = false;
  }
};

const submitCreateUser = async () => {
  if (!createUserForm.value.roleObj) {
    alert("Please select a role");
    return;
  }
  isCreatingUser.value = true;
  try {
    const res = await axios.post('/api/users', {
      name: createUserForm.value.name,
      email: createUserForm.value.email,
      password: createUserForm.value.password,
      role_id: createUserForm.value.roleObj.id,
      permissions: []
    });
    
    await fetchData(); // reload users list
    
    // Select the new user automatically
    selectedUserObj.value = users.value.find(u => u.id === res.data.id);
    loadUserPermissions();
    
    document.getElementById('closeCreateUserModal').click();
    
    // Reset form
    createUserForm.value = { name: '', email: '', password: '', roleObj: null };
    alert('User created successfully!');
  } catch (error) {
    alert('Failed to create user: ' + (error.response?.data?.message || 'Unknown error'));
  } finally {
    isCreatingUser.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.hover-shadow:hover {
  box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
  transform: translateY(-2px);
}
.transition {
  transition: all .2s ease-in-out;
}
</style>
