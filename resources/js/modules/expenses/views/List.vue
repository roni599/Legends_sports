<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Expense Management</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search description..." style="width: 200px;">
        <button class="btn btn-warning btn-sm text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#categoriesModal">
          Manage Categories
        </button>
        <button class="btn btn-primary btn-sm" @click="openExpenseModal(null)">
          + Add Expense
        </button>
      </div>
    </div>
    
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Category</th>
              <th>Description</th>
              <th class="text-end">Amount (৳)</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="expenseStore.loading">
              <td colspan="5" class="text-center py-4">Loading expenses...</td>
            </tr>
            <tr v-else-if="expenseStore.expenses.length === 0">
              <td colspan="5" class="text-center py-4">No expenses found</td>
            </tr>
            <tr v-else v-for="expense in expenseStore.expenses" :key="expense.id">
              <td>{{ expense.date }}</td>
              <td><span class="badge bg-secondary">{{ expense.category?.name || 'Unknown' }}</span></td>
              <td>{{ expense.description || '-' }}</td>
              <td class="text-end text-danger fw-bold">{{ expense.amount }}</td>
              <td class="text-end">
                <button @click="openExpenseModal(expense)" class="btn btn-sm btn-outline-info me-2">Edit</button>
                <button @click="expenseStore.deleteExpense(expense.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="text-secondary small">Showing {{ expenseStore.expenses.length }} entries of {{ expenseStore.total }}</span>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="expenseStore.page === 1" @click="changePage(expenseStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="expenseStore.page * expenseStore.perPage >= expenseStore.total" @click="changePage(expenseStore.page + 1)">Next</button>
        </div>
      </div>
    </div>

    <!-- Expense Modal -->
    <div class="modal fade" id="expenseModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">{{ editingExpenseId ? 'Edit Expense' : 'Add New Expense' }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="submitExpense">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Category *</label>
                <select v-model="expenseForm.expense_category_id" class="form-select custom-input" required>
                  <option value="">Select a category</option>
                  <option v-for="cat in expenseStore.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Amount (৳) *</label>
                <input type="number" v-model.number="expenseForm.amount" class="form-control custom-input" min="1" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Date *</label>
                <input type="date" v-model="expenseForm.date" class="form-control custom-input" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Description (Optional)</label>
                <textarea v-model="expenseForm.description" class="form-control custom-input" rows="2"></textarea>
              </div>
            </div>
            <div class="modal-footer border-secondary">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="closeExpenseModalBtn">Close</button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                {{ editingExpenseId ? 'Update' : 'Save' }} Expense
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Categories Modal -->
    <div class="modal fade" id="categoriesModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Manage Expense Categories</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitCategory" class="d-flex gap-2 mb-4">
              <input type="text" v-model="newCategoryName" class="form-control custom-input" placeholder="Category name..." required>
              <button type="submit" class="btn btn-success" :disabled="isSubmittingCat">{{ editingCatId ? 'Update' : 'Add' }}</button>
              <button v-if="editingCatId" type="button" class="btn btn-secondary" @click="cancelEditCat">Cancel</button>
            </form>
            
            <ul class="list-group list-group-flush">
              <li v-for="cat in expenseStore.categories" :key="cat.id" class="list-group-item bg-transparent text-light border-secondary d-flex justify-content-between align-items-center px-0">
                {{ cat.name }}
                <div>
                  <button @click="editCategory(cat)" class="btn btn-sm btn-outline-info me-2">Edit</button>
                  <button @click="expenseStore.deleteCategory(cat.id)" class="btn btn-sm btn-outline-danger">X</button>
                </div>
              </li>
              <li v-if="expenseStore.categories.length === 0" class="list-group-item bg-transparent text-secondary border-0 text-center">No categories found</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useExpenseStore } from '../../../store/expenses';

const expenseStore = useExpenseStore();
const searchQuery = ref('');
let searchTimeout = null;

// Modals state
const closeExpenseModalBtn = ref(null);
let bsExpenseModal = null;
const isSubmitting = ref(false);
const isSubmittingCat = ref(false);
const newCategoryName = ref('');
const editingCatId = ref(null);
const editingExpenseId = ref(null);

const expenseForm = ref({
  expense_category_id: '',
  amount: '',
  date: new Date().toISOString().split('T')[0],
  description: ''
});

onMounted(() => {
  bsExpenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
  expenseStore.fetchExpenses();
  expenseStore.fetchCategories();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    expenseStore.fetchExpenses(1, searchQuery.value);
  }, 300);
};

const changePage = (page) => {
  expenseStore.fetchExpenses(page, searchQuery.value);
};

const editCategory = (cat) => {
  editingCatId.value = cat.id;
  newCategoryName.value = cat.name;
};

const cancelEditCat = () => {
  editingCatId.value = null;
  newCategoryName.value = '';
};

const submitCategory = async () => {
  if (!newCategoryName.value) return;
  isSubmittingCat.value = true;
  try {
    if (editingCatId.value) {
      await expenseStore.updateCategory(editingCatId.value, newCategoryName.value);
    } else {
      await expenseStore.addCategory(newCategoryName.value);
    }
    cancelEditCat();
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to save category');
  } finally {
    isSubmittingCat.value = false;
  }
};

const openExpenseModal = (expense = null) => {
  if (expense) {
    editingExpenseId.value = expense.id;
    expenseForm.value = {
      expense_category_id: expense.expense_category_id,
      amount: expense.amount,
      date: expense.date,
      description: expense.description || ''
    };
  } else {
    editingExpenseId.value = null;
    expenseForm.value = {
      expense_category_id: '',
      amount: '',
      date: new Date().toISOString().split('T')[0],
      description: ''
    };
  }
  bsExpenseModal.show();
};

const submitExpense = async () => {
  isSubmitting.value = true;
  try {
    if (editingExpenseId.value) {
      await expenseStore.updateExpense(editingExpenseId.value, expenseForm.value);
    } else {
      await expenseStore.addExpense(expenseForm.value);
    }
    closeExpenseModalBtn.value.click();
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to save expense');
  } finally {
    isSubmitting.value = false;
  }
};
</script>
