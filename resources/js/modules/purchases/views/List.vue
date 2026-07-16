<template>
  <div class="content-card">
    <div class="content-header d-flex justify-content-between align-items-center">
      <h3 class="fs-5 m-0 text-light">Purchase (Stock In) History</h3>
      <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" @click="openPurchaseModal">
          + New Purchase (Stock In)
        </button>
      </div>
    </div>
    
    <div class="p-4">
      <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Ref No</th>
              <th>Supplier</th>
              <th class="text-end">Total Amount (৳)</th>
              <th class="text-end">Paid (৳)</th>
              <th class="text-end">Due (৳)</th>
              <th class="text-center">Items</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="purchaseStore.loading && purchaseStore.purchases.length === 0">
              <td colspan="7" class="text-center py-4">Loading purchases...</td>
            </tr>
            <tr v-else-if="purchaseStore.purchases.length === 0">
              <td colspan="7" class="text-center py-4">No purchases found</td>
            </tr>
            <tr v-else v-for="purchase in purchaseStore.purchases" :key="purchase.id">
              <td>{{ purchase.purchase_date }}</td>
              <td>{{ purchase.reference_no || '-' }}</td>
              <td>{{ purchase.supplier?.name }}</td>
              <td class="text-end text-info fw-bold">{{ purchase.grand_total }}</td>
              <td class="text-end text-success">{{ purchase.paid_amount }}</td>
              <td class="text-end" :class="purchase.due_amount > 0 ? 'text-danger fw-bold' : ''">{{ purchase.due_amount }}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-secondary" @click="viewItems(purchase)">
                  View ({{ purchase.items?.length || 0 }})
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
        <div class="text-secondary small mb-2 mb-md-0">
          Showing {{ purchaseStore.purchases.length === 0 ? 0 : ((purchaseStore.page - 1) * purchaseStore.perPage) + 1 }} to {{ Math.min(purchaseStore.page * purchaseStore.perPage, purchaseStore.total) }} of {{ purchaseStore.total }} entries
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="purchaseStore.page === 1" @click="changePage(purchaseStore.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="purchaseStore.page * purchaseStore.perPage >= purchaseStore.total" @click="changePage(purchaseStore.page + 1)">Next</button>
        </div>
      </div>
    </div>

    <!-- New Purchase Modal -->
    <div class="modal fade" id="purchaseModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Create New Purchase (Stock In)</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <form @submit.prevent="submitPurchase">
            <div class="modal-body p-4">
              <div class="row mb-4">
                <div class="col-md-4">
                  <label class="form-label">Supplier *</label>
                  <VueMultiselect 
                    v-model="purchaseForm.supplierObj" 
                    :options="supplierStore.suppliers" 
                    track-by="id" 
                    label="name" 
                    placeholder="Select Supplier"
                    :searchable="true" 
                    :close-on-select="true" 
                    :show-labels="false"
                    class="custom-multiselect"
                  />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Purchase Date *</label>
                  <input type="date" v-model="purchaseForm.purchase_date" class="form-control custom-input" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Reference No (Invoice/Bill)</label>
                  <input type="text" v-model="purchaseForm.reference_no" class="form-control custom-input" placeholder="e.g. INV-1002">
                </div>
              </div>

              <div class="card bg-black border-secondary mb-4">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                  <h6 class="m-0">Purchase Items</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="addItemRow">
                    + Add Product Row
                  </button>
                </div>
                <div class="card-body p-0">
                  <table class="table table-dark table-borderless mb-0">
                    <thead>
                      <tr>
                        <th width="40%">Product</th>
                        <th width="15%">Quantity</th>
                        <th width="20%">Unit Cost (৳)</th>
                        <th width="20%">Subtotal (৳)</th>
                        <th width="5%"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in purchaseForm.items" :key="index">
                        <td>
                          <VueMultiselect 
                            v-model="item.productObj" 
                            :options="productStore.products" 
                            track-by="id" 
                            label="name" 
                            placeholder="Select Product"
                            :searchable="true" 
                            :close-on-select="true" 
                            :show-labels="false"
                            class="custom-multiselect"
                          />
                        </td>
                        <td>
                          <input type="number" v-model.number="item.quantity" class="form-control custom-input" min="1" required>
                        </td>
                        <td>
                          <input type="number" v-model.number="item.unit_cost" class="form-control custom-input" min="0" step="0.01" required>
                        </td>
                        <td>
                          <input type="text" class="form-control custom-input-readonly" :value="(item.quantity * item.unit_cost).toFixed(2)" readonly>
                        </td>
                        <td>
                          <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItemRow(index)" v-if="purchaseForm.items.length > 1">
                            ✕
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="row justify-content-end">
                <div class="col-md-4">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="fs-5">Grand Total:</span>
                    <span class="fs-5 text-info fw-bold">৳{{ grandTotal.toFixed(2) }}</span>
                  </div>
                  <div class="d-flex align-items-center mb-2">
                    <label class="me-2 text-nowrap">Paid Amount: ৳</label>
                    <input type="number" v-model.number="purchaseForm.paid_amount" class="form-control custom-input" min="0" step="0.01" required>
                  </div>
                  <div class="d-flex justify-content-between text-danger">
                    <span>Due Amount:</span>
                    <span class="fw-bold">৳{{ dueAmount.toFixed(2) }}</span>
                  </div>
                </div>
              </div>

            </div>
            <div class="modal-footer border-secondary">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" ref="closePurchaseModalBtn">Cancel</button>
              <button type="submit" class="btn btn-success" :disabled="isSubmitting || purchaseForm.items.length === 0">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
                Save Purchase & Update Stock
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- View Items Modal -->
    <div class="modal fade" id="viewItemsModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Purchase Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-0">
             <table class="table table-dark table-striped mb-0">
                <thead class="bg-black">
                  <tr>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in activePurchase?.items" :key="item.id">
                    <td>{{ item.product?.name }}</td>
                    <td class="text-center">{{ item.quantity }}</td>
                    <td class="text-end">{{ item.unit_cost }}</td>
                    <td class="text-end text-info">{{ item.subtotal }}</td>
                  </tr>
                </tbody>
             </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePurchaseStore } from '../../../store/purchases';
import { useSupplierStore } from '../../../store/suppliers';
import { useProductStore } from '../../../store/products';
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';

const purchaseStore = usePurchaseStore();
const supplierStore = useSupplierStore();
const productStore = useProductStore();

let bsPurchaseModal = null;
let bsViewItemsModal = null;
const closePurchaseModalBtn = ref(null);
const isSubmitting = ref(false);
const activePurchase = ref(null);

const purchaseForm = ref({
  supplierObj: null,
  purchase_date: new Date().toISOString().split('T')[0],
  reference_no: '',
  paid_amount: 0,
  items: [
    { productObj: null, quantity: 1, unit_cost: 0 }
  ]
});

const grandTotal = computed(() => {
  return purchaseForm.value.items.reduce((total, item) => {
    return total + (item.quantity * item.unit_cost);
  }, 0);
});

const dueAmount = computed(() => {
  return Math.max(0, grandTotal.value - purchaseForm.value.paid_amount);
});

onMounted(() => {
  bsPurchaseModal = new bootstrap.Modal(document.getElementById('purchaseModal'));
  bsViewItemsModal = new bootstrap.Modal(document.getElementById('viewItemsModal'));
  purchaseStore.fetchPurchases();
  supplierStore.fetchSuppliers(1, '', 100); // load a bunch for the dropdown
  productStore.fetchProducts(); // fetch for dropdown
});

const changePage = (page) => {
  purchaseStore.fetchPurchases(page);
};

const openPurchaseModal = () => {
  purchaseForm.value = {
    supplierObj: null,
    purchase_date: new Date().toISOString().split('T')[0],
    reference_no: '',
    paid_amount: 0,
    items: [
      { productObj: null, quantity: 1, unit_cost: 0 }
    ]
  };
  bsPurchaseModal.show();
};

const addItemRow = () => {
  purchaseForm.value.items.push({ productObj: null, quantity: 1, unit_cost: 0 });
};

const removeItemRow = (index) => {
  purchaseForm.value.items.splice(index, 1);
};

const viewItems = (purchase) => {
  activePurchase.value = purchase;
  bsViewItemsModal.show();
};

const submitPurchase = async () => {
  if (purchaseForm.value.items.length === 0) return;
  if (!purchaseForm.value.supplierObj) {
    alert('Please select a supplier');
    return;
  }
  
  // Create submission payload
  const payload = {
    supplier_id: purchaseForm.value.supplierObj.id,
    purchase_date: purchaseForm.value.purchase_date,
    reference_no: purchaseForm.value.reference_no,
    paid_amount: purchaseForm.value.paid_amount,
    items: purchaseForm.value.items.map(i => ({
      product_id: i.productObj ? i.productObj.id : null,
      quantity: i.quantity,
      unit_cost: i.unit_cost
    })).filter(i => i.product_id !== null)
  };

  if (payload.items.length === 0) {
    alert('Please select at least one product');
    return;
  }
  
  isSubmitting.value = true;
  try {
    await purchaseStore.addPurchase(payload);
    closePurchaseModalBtn.value.click();
    alert('Purchase saved successfully. Stock has been updated!');
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to save purchase');
  } finally {
    isSubmitting.value = false;
  }
};
</script>
