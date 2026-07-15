<template>
  <div class="pos-container content-card p-0 d-flex flex-column h-100">
    <div class="content-header d-flex justify-content-between align-items-center px-4 py-3 border-bottom border-secondary">
      <h3 class="fs-5 m-0 text-light">Point of Sale (POS)</h3>
      <div class="d-flex gap-2">
        <input type="text" v-model="searchQuery" @input="handleSearch" class="form-control custom-input" placeholder="Search products..." style="width: 300px;">
        <select v-model="categoryFilter" @change="fetchProducts" class="form-select custom-input" style="width: 150px;">
          <option value="">All Categories</option>
          <option value="food">Food</option>
          <option value="beverage">Beverage</option>
          <option value="equipment">Equipment</option>
          <option value="other">Other</option>
        </select>
      </div>
    </div>
    
    <div class="d-flex flex-grow-1 overflow-hidden">
      <!-- Product Grid -->
      <div class="col-md-8 p-4 overflow-auto" style="height: calc(100vh - 150px);">
        <div v-if="loading" class="text-center py-5 text-secondary">Loading products...</div>
        <div v-else-if="products.length === 0" class="text-center py-5 text-secondary">No products available in stock.</div>
        <div v-else class="row g-3">
          <div class="col-xl-3 col-lg-4 col-md-6" v-for="product in products" :key="product.id">
            <div class="card h-100 bg-dark border-secondary product-card text-light" @click="addToCart(product)" :class="{'opacity-50': product.stock_quantity === 0}">
              <div class="card-body text-center d-flex flex-column justify-content-center">
                <h6 class="card-title fw-bold mb-1">{{ product.name }}</h6>
                <span class="badge bg-secondary mb-2 mx-auto" style="width: fit-content;">{{ product.category }}</span>
                <div class="fs-5 text-success fw-bold mb-1">৳{{ product.price }}</div>
                <small class="text-secondary" v-if="product.stock_quantity > 0">Stock: {{ product.stock_quantity }}</small>
                <small class="text-danger fw-bold" v-else>Out of Stock</small>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Cart Panel -->
      <div class="col-md-4 bg-dark border-start border-secondary d-flex flex-column" style="height: calc(100vh - 150px);">
        <div class="p-3 border-bottom border-secondary bg-black">
          <h5 class="m-0 text-light">Current Cart</h5>
        </div>
        
        <div class="flex-grow-1 overflow-auto p-3">
          <div v-if="cart.length === 0" class="text-center py-5 text-secondary">Cart is empty</div>
          <div v-else class="cart-item mb-3 p-2 border border-secondary rounded" v-for="(item, index) in cart" :key="index">
            <div class="d-flex justify-content-between mb-2">
              <span class="fw-bold text-light">{{ item.name }}</span>
              <span class="text-success">৳{{ item.price * item.quantity }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-secondary small">৳{{ item.price }} / unit</span>
              <div class="input-group input-group-sm" style="width: 100px;">
                <button class="btn btn-outline-secondary" @click="updateQuantity(index, -1)">-</button>
                <input type="text" class="form-control text-center bg-dark text-light border-secondary" :value="item.quantity" readonly>
                <button class="btn btn-outline-secondary" @click="updateQuantity(index, 1)">+</button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="p-3 border-top border-secondary bg-black">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Subtotal</span>
            <span class="text-light">৳{{ subtotal }}</span>
          </div>
          <div class="d-flex justify-content-between mb-3 align-items-center">
            <span class="text-secondary">Discount</span>
            <div class="input-group input-group-sm w-50">
              <span class="input-group-text bg-dark text-secondary border-secondary">৳</span>
              <input type="number" v-model.number="discount" class="form-control bg-dark text-light border-secondary text-end" min="0">
            </div>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span class="fs-5 fw-bold text-light">Total</span>
            <span class="fs-5 fw-bold text-success">৳{{ grandTotal }}</span>
          </div>
          
          <button class="btn btn-success w-100 fw-bold py-2 mb-2" :disabled="cart.length === 0" @click="openPaymentModal">
            Checkout
          </button>
          <button class="btn btn-outline-danger w-100" :disabled="cart.length === 0" @click="cart = []; discount = 0">
            Clear Cart
          </button>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Payment Collection</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <h3 class="text-success mb-4">Total: ৳{{ grandTotal }}</h3>
            
            <div class="mb-3 text-start">
              <label class="form-label">Cash Received (৳)</label>
              <input type="number" v-model.number="paidAmount" class="form-control custom-input fs-4 py-2" required>
            </div>
            
            <div class="d-flex justify-content-between bg-black p-3 rounded mb-3">
              <span class="text-secondary">Change Amount:</span>
              <span class="fw-bold fs-5" :class="changeAmount >= 0 ? 'text-warning' : 'text-danger'">৳{{ Math.abs(changeAmount) }} {{ changeAmount < 0 ? '(Due)' : '' }}</span>
            </div>
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="closePaymentModalBtn">Cancel</button>
            <button type="button" class="btn btn-success px-4" @click="processCheckout" :disabled="isSubmitting || paidAmount < 0">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
              Confirm Sale
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const products = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const categoryFilter = ref('');
let searchTimeout = null;

const cart = ref([]);
const discount = ref(0);
const paidAmount = ref(0);

const closePaymentModalBtn = ref(null);
let bsPaymentModal = null;
const isSubmitting = ref(false);

const fetchProducts = async () => {
  loading.value = true;
  try {
    let url = `/api/products?all=true&active_only=true`;
    if (searchQuery.value) url += `&search=${searchQuery.value}`;
    
    const response = await axios.get(url);
    let data = response.data;
    
    if (categoryFilter.value) {
      data = data.filter(p => p.category === categoryFilter.value);
    }
    products.value = data;
  } catch (error) {
    console.error('Failed to fetch products', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  bsPaymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
  fetchProducts();
});

const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchProducts();
  }, 300);
};

const addToCart = (product) => {
  if (product.stock_quantity === 0) return;
  
  const existing = cart.value.find(i => i.product_id === product.id);
  if (existing) {
    if (existing.quantity < product.stock_quantity) {
      existing.quantity++;
    } else {
      alert(`Only ${product.stock_quantity} items available in stock.`);
    }
  } else {
    cart.value.push({
      product_id: product.id,
      name: product.name,
      price: product.price,
      quantity: 1,
      max_stock: product.stock_quantity
    });
  }
};

const updateQuantity = (index, delta) => {
  const item = cart.value[index];
  const newQ = item.quantity + delta;
  
  if (newQ === 0) {
    cart.value.splice(index, 1);
  } else if (newQ > 0 && newQ <= item.max_stock) {
    item.quantity = newQ;
  }
};

const subtotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const grandTotal = computed(() => {
  return Math.max(0, subtotal.value - (discount.value || 0));
});

const changeAmount = computed(() => {
  return (paidAmount.value || 0) - grandTotal.value;
});

const openPaymentModal = () => {
  paidAmount.value = grandTotal.value;
  bsPaymentModal.show();
};

const processCheckout = async () => {
  isSubmitting.value = true;
  try {
    const payload = {
      cart: cart.value.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        price: i.price
      })),
      discount: discount.value || 0,
      paid: paidAmount.value || 0
    };
    
    await axios.post('/api/pos/checkout', payload);
    
    closePaymentModalBtn.value.click();
    cart.value = [];
    discount.value = 0;
    paidAmount.value = 0;
    
    // Refresh products to show updated stock
    fetchProducts();
    
    alert('Sale completed successfully!');
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to complete sale');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.product-card {
  cursor: pointer;
  transition: all 0.2s;
}
.product-card:hover {
  transform: translateY(-2px);
  border-color: #0d6efd !important;
  box-shadow: 0 4px 8px rgba(0,0,0,0.5);
}
</style>
