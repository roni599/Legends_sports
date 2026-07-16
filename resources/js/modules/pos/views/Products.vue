<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">POS Products</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control form-control-sm custom-input" placeholder="Search product..." style="width: 200px;">
        <button class="btn btn-primary btn-sm" @click="openProductModal(null)">
          + Add Product
        </button>
      </div>
    </div>
    
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>SL</th>
              <th>Name</th>
              <th>Barcode</th>
              <th>Category</th>
              <th>Price (৳)</th>
              <th>Stock</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="productStore.loading">
              <td colspan="7" class="text-center py-4">Loading products...</td>
            </tr>
            <tr v-else-if="productStore.products.length === 0">
              <td colspan="8" class="text-center py-4">No products found</td>
            </tr>
            <tr v-else v-for="(product, index) in productStore.products" :key="product.id">
              <td>{{ index + 1 }}</td>
              <td>{{ product.name }}</td>
              <td>{{ product.barcode || '-' }}</td>
              <td><span class="badge bg-secondary">{{ product.category }}</span></td>
              <td class="text-success fw-bold">{{ product.price }}</td>
              <td>
                <span :class="{'text-danger fw-bold': product.stock_quantity === 0}">{{ product.stock_quantity }}</span>
              </td>
              <td>
                <span v-if="product.is_active" class="badge bg-success">Active</span>
                <span v-else class="badge bg-danger">Inactive</span>
              </td>
              <td class="text-end">
                <button @click="openProductModal(product)" class="btn btn-sm btn-outline-info me-2">Edit</button>
                <button @click="productStore.deleteProduct(product.id)" class="btn btn-sm btn-outline-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
        <div class="text-secondary small mb-2 mb-md-0">
          Showing {{ productStore.products.length === 0 ? 0 : ((productStore.page - 1) * productStore.perPage) + 1 }} to {{ Math.min(productStore.page * productStore.perPage, productStore.total) }} of {{ productStore.total }} entries
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="productStore.page === 1" @click="changePage(productStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="productStore.page * productStore.perPage >= productStore.total" @click="changePage(productStore.page + 1)">Next</button>
        </div>
      </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">{{ editingProductId ? 'Edit Product' : 'Add New Product' }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="submitProduct">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Product Name *</label>
                <input type="text" v-model="productForm.name" class="form-control custom-input" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Barcode (Optional)</label>
                <input type="text" v-model="productForm.barcode" class="form-control custom-input" placeholder="Scan or type barcode">
              </div>
              <div class="mb-3">
                <label class="form-label">Category *</label>
                <select v-model="productForm.category" class="form-select custom-input" required>
                  <option value="food">Food</option>
                  <option value="beverage">Beverage</option>
                  <option value="equipment">Equipment (Rental)</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Unit Price (৳) *</label>
                  <input type="number" v-model.number="productForm.price" class="form-control custom-input" min="0" step="0.01" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Stock Quantity *</label>
                  <input type="number" v-model.number="productForm.stock_quantity" class="form-control custom-input" min="0" required>
                </div>
              </div>
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="isActiveCheck" v-model="productForm.is_active">
                <label class="form-check-label" for="isActiveCheck">Product is Active</label>
              </div>
            </div>
            <div class="modal-footer border-secondary">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="closeProductModalBtn">Close</button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                {{ editingProductId ? 'Update' : 'Save' }} Product
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
import { useProductStore } from '../../../store/products';

const productStore = useProductStore();
const searchQuery = ref('');
let searchTimeout = null;

// Modals state
const closeProductModalBtn = ref(null);
let bsProductModal = null;
const isSubmitting = ref(false);
const editingProductId = ref(null);

const productForm = ref({
  name: '',
  barcode: '',
  category: 'food',
  price: 0,
  stock_quantity: 0,
  is_active: true
});

onMounted(() => {
  bsProductModal = new bootstrap.Modal(document.getElementById('productModal'));
  productStore.fetchProducts();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    productStore.fetchProducts(1, searchQuery.value);
  }, 300);
};

const changePage = (page) => {
  productStore.fetchProducts(page, searchQuery.value);
};

const openProductModal = (product = null) => {
  if (product) {
    editingProductId.value = product.id;
    productForm.value = {
      name: product.name,
      barcode: product.barcode || '',
      category: product.category,
      price: product.price,
      stock_quantity: product.stock_quantity,
      is_active: product.is_active
    };
  } else {
    editingProductId.value = null;
    productForm.value = {
      name: '',
      barcode: '',
      category: 'food',
      price: 0,
      stock_quantity: 0,
      is_active: true
    };
  }
  bsProductModal.show();
};

const submitProduct = async () => {
  isSubmitting.value = true;
  try {
    if (editingProductId.value) {
      await productStore.updateProduct(editingProductId.value, productForm.value);
    } else {
      await productStore.addProduct(productForm.value);
    }
    closeProductModalBtn.value.click();
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to save product');
  } finally {
    isSubmitting.value = false;
  }
};
</script>
