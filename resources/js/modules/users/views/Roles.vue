<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <div>
        <h3 class="fs-5 m-0 text-light">User Roles</h3>
        <p class="text-secondary small mb-0 mt-1">Manage system roles and their default permissions.</p>
      </div>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm text-dark" placeholder="Search role name..." style="width: 200px;" v-if="!isEditorOpen">
        <button class="btn btn-primary btn-sm" @click="createNewRole" v-if="!isEditorOpen">
          + Add New Role
        </button>
      </div>
    </div>

    <!-- Roles Table -->
    <div class="p-4" v-if="!isEditorOpen">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>SL</th>
              <th>Role Name</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
              <tr v-if="roleStore.loading">
                <td colspan="3" class="text-center py-4">
                  <span class="spinner-border spinner-border-sm text-primary"></span> Loading...
                </td>
              </tr>
              <tr v-else-if="roleStore.roles.length === 0">
                <td colspan="3" class="text-center py-4">No roles found</td>
              </tr>
              <tr v-for="(role, index) in roleStore.roles" :key="role.id">
                <td>{{ index + 1 }}</td>
                <td class="fw-bold">{{ role.name }}</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-2" @click="editRole(role)">
                    Edit Permissions
                  </button>
                  <button class="btn btn-sm btn-outline-danger" @click="deleteRole(role)" v-if="role.slug !== 'super-admin'">
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    <!-- Permissions Editor (Visible only when editing or creating a role) -->
    <div v-if="isEditorOpen" class="card shadow-sm border-secondary bg-dark text-light">
      <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
        <div>
          <button class="btn btn-sm btn-outline-secondary me-3 text-light" @click="closeEditor">
            ← Back to Roles
          </button>
          <h5 class="m-0 fw-bold text-light d-inline">
            {{ isCreating ? 'Create New Role' : 'Permissions for ' + editingRole.name }}
          </h5>
        </div>
        <button class="btn btn-primary px-4" @click="saveRole" :disabled="isSaving">
          <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
          {{ isCreating ? 'Create Role' : 'Save Permissions' }}
        </button>
      </div>
      <div class="card-body">
        
        <div v-if="isCreating" class="mb-4 col-md-6">
          <label class="form-label fw-bold text-light">Role Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control text-dark" v-model="newRoleName" placeholder="e.g. Area Manager" required>
        </div>

        <h6 class="fw-bold text-light mb-4 border-bottom border-secondary pb-2">Select Accessible Segments</h6>
        
        <div v-if="loadingPermissions" class="text-center py-5">
           <span class="spinner-border spinner-border-sm text-primary"></span> Loading Permissions...
        </div>

        <!-- Checkbox Segments -->
        <div class="row g-4" v-else>
          <div class="col-md-6 col-lg-4" v-for="(perms, groupName) in groupedPermissions" :key="groupName">
            <div class="card h-100 border-secondary bg-dark shadow-sm hover-shadow transition">
              <div class="card-header border-secondary py-3">
                <div class="form-check form-switch d-flex align-items-center">
                  <input class="form-check-input me-3 shadow-none mt-0" 
                         style="width: 40px; height: 20px; cursor:pointer;" 
                         type="checkbox" 
                         :id="'group-' + groupName"
                         :checked="isGroupFullySelected(groupName)"
                         @change="toggleGroup(groupName, $event.target.checked)">
                  <label class="form-check-label fw-bold text-info m-0" :for="'group-' + groupName" style="cursor:pointer; font-size: 1.1rem;">
                    {{ formatGroupName(groupName) }} Segment
                  </label>
                </div>
              </div>
              <div class="card-body">
                <div class="form-check mb-3 ms-2" v-for="permission in perms" :key="permission.id">
                  <input class="form-check-input" 
                         type="checkbox" 
                         :value="permission.id" 
                         v-model="selectedPermissions"
                         :id="'perm-' + permission.id">
                  <label class="form-check-label text-light" :for="'perm-' + permission.id">
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
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoleStore } from '../../../store/roles';
import Swal from 'sweetalert2';

const roleStore = useRoleStore();

const isEditorOpen = ref(false);
const isCreating = ref(false);
const editingRole = ref(null);
const newRoleName = ref('');
const selectedPermissions = ref([]);
const isSaving = ref(false);
const loadingPermissions = ref(false);
const searchQuery = ref('');
let searchTimeout = null;

onMounted(() => {
  roleStore.fetchRoles();
  roleStore.fetchPermissions();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    roleStore.fetchRoles(searchQuery.value);
  }, 500);
};

const groupedPermissions = computed(() => {
  const groups = {};
  roleStore.permissions.forEach(p => {
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
  return groupPerms.every(p => selectedPermissions.value.includes(p.id));
};

const toggleGroup = (groupName, isChecked) => {
  const groupPerms = groupedPermissions.value[groupName];
  if (isChecked) {
    groupPerms.forEach(p => {
      if (!selectedPermissions.value.includes(p.id)) {
        selectedPermissions.value.push(p.id);
      }
    });
  } else {
    selectedPermissions.value = selectedPermissions.value.filter(
      id => !groupPerms.some(p => p.id === id)
    );
  }
};

const createNewRole = () => {
  isEditorOpen.value = true;
  isCreating.value = true;
  editingRole.value = null;
  newRoleName.value = '';
  selectedPermissions.value = [];
};

const closeEditor = () => {
  isEditorOpen.value = false;
  isCreating.value = false;
  editingRole.value = null;
  newRoleName.value = '';
  selectedPermissions.value = [];
};

const editRole = async (role) => {
  isEditorOpen.value = true;
  isCreating.value = false;
  editingRole.value = role;
  loadingPermissions.value = true;
  try {
    const roleData = await roleStore.fetchRole(role.id);
    selectedPermissions.value = roleData.permissions.map(p => p.id);
  } catch (error) {
    Swal.fire('Error', 'Failed to load role permissions.', 'error');
    closeEditor();
  } finally {
    loadingPermissions.value = false;
  }
};

const saveRole = async () => {
  if (isCreating.value && !newRoleName.value.trim()) {
    Swal.fire('Error', 'Role name is required.', 'error');
    return;
  }

  isSaving.value = true;
  try {
    if (isCreating.value) {
      await roleStore.createRole(newRoleName.value, selectedPermissions.value);
      Swal.fire('Success', 'Role created successfully!', 'success');
    } else {
      await roleStore.updateRolePermissions(editingRole.value.id, selectedPermissions.value);
      Swal.fire('Success', 'Role permissions updated successfully!', 'success');
    }
    closeEditor();
  } catch (error) {
    let msg = error.response?.data?.message || 'Failed to save.';
    if (error.response?.data?.errors?.name) {
      msg = error.response.data.errors.name[0];
    }
    Swal.fire('Error', msg, 'error');
  } finally {
    isSaving.value = false;
  }
};

const deleteRole = (role) => {
  Swal.fire({
    title: 'Are you sure?',
    text: `You are about to delete the role: ${role.name}. This action cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await roleStore.deleteRole(role.id);
        Swal.fire('Deleted!', 'Role has been deleted.', 'success');
      } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'Cannot delete this role', 'error');
      }
    }
  });
};
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
