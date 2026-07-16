<template>
  <div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
      <div class="col">
        <h2 class="h3 mb-0 text-gray-800">User Roles</h2>
        <p class="text-muted mb-0">Manage system roles and their default permissions.</p>
      </div>
    </div>

    <!-- Roles Table -->
    <div class="card shadow-sm border-0 mb-4" v-if="!editingRole">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
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
    </div>

    <!-- Permissions Editor (Visible only when editing a role) -->
    <div v-if="editingRole" class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
          <button class="btn btn-sm btn-outline-secondary me-3" @click="editingRole = null">
            ← Back to Roles
          </button>
          <h5 class="m-0 fw-bold text-primary d-inline">Permissions for {{ editingRole.name }}</h5>
        </div>
        <button class="btn btn-primary px-4" @click="savePermissions" :disabled="isSaving">
          <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
          Save Permissions
        </button>
      </div>
      <div class="card-body">
        <h6 class="fw-bold text-dark mb-4 border-bottom pb-2">Select Accessible Segments</h6>
        
        <div v-if="loadingPermissions" class="text-center py-5">
           <span class="spinner-border spinner-border-sm text-primary"></span> Loading Permissions...
        </div>

        <!-- Checkbox Segments -->
        <div class="row g-4" v-else>
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
                         v-model="selectedPermissions"
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
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoleStore } from '../../../store/roles';
import Swal from 'sweetalert2';

const roleStore = useRoleStore();

const editingRole = ref(null);
const selectedPermissions = ref([]);
const isSaving = ref(false);
const loadingPermissions = ref(false);

onMounted(() => {
  roleStore.fetchRoles();
  roleStore.fetchPermissions();
});

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

const editRole = async (role) => {
  editingRole.value = role;
  loadingPermissions.value = true;
  try {
    const roleData = await roleStore.fetchRole(role.id);
    selectedPermissions.value = roleData.permissions.map(p => p.id);
  } catch (error) {
    Swal.fire('Error', 'Failed to load role permissions.', 'error');
    editingRole.value = null;
  } finally {
    loadingPermissions.value = false;
  }
};

const savePermissions = async () => {
  if (!editingRole.value) return;
  isSaving.value = true;
  try {
    await roleStore.updateRolePermissions(editingRole.value.id, selectedPermissions.value);
    Swal.fire('Success', 'Role permissions updated successfully!', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to save permissions.', 'error');
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
