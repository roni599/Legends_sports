<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-1 text-primary fw-bold"><i class="bi bi-plus-circle me-2"></i>Create New Booking</h4>
        <p class="text-muted mb-0">Fill in the details to create a new booking</p>
      </div>
      <div>
        <router-link to="/bookings/calendar" class="btn btn-outline-secondary me-2">
          <i class="bi bi-calendar3 me-1"></i> Calendar View
        </router-link>
        <router-link to="/bookings" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i> Back to List
        </router-link>
      </div>
    </div>

    <div class="content-card">
      <div class="p-4">
        <form @submit.prevent="submitBooking">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold text-secondary">Select Client</label>
              <div class="d-flex align-items-stretch">
                <div class="client-multiselect-wrap flex-grow-1">
                  <VueMultiselect
                    v-model="selectedClientObj"
                    :options="clientStore.allClients"
                    :custom-label="clientLabel"
                    track-by="id"
                    placeholder="-- Search Client --"
                    :searchable="true"
                    :allow-empty="false"
                    class="dark-multiselect"
                  >
                  </VueMultiselect>
                </div>
                <button type="button" class="btn btn-success px-3 ms-2 d-flex align-items-center" @click="openClientModal" title="Add New Client">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-secondary">Select Ground</label>
              <div class="d-flex align-items-stretch">
                <div class="client-multiselect-wrap flex-grow-1">
                  <VueMultiselect
                    v-model="selectedGroundObj"
                    :options="bookingStore.activeGrounds"
                    :custom-label="groundLabel"
                    track-by="id"
                    placeholder="Press Enter to select"
                    :searchable="true"
                    :allow-empty="false"
                    class="dark-multiselect"
                    @select="onGroundSelect"
                  >
                  </VueMultiselect>
                </div>
                <button type="button" class="btn btn-success px-3 ms-2 d-flex align-items-center" @click="openGroundModal" title="Add New Ground">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-secondary">Date</label>
              <input type="date" v-model="form.date" class="form-control dark-input" :min="todayStr" @change="onDateChange" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-secondary">Start Time</label>
              <input type="time" v-model="form.start_time" :min="minTime" class="form-control dark-input" @input="onStartTimeChange" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold text-secondary">End Time</label>
              <input type="time" v-model="form.end_time" class="form-control dark-input" @change="calculatePrice" required>
            </div>

            <div class="col-12" v-if="bookingStore.pricePreview">
              <div class="alert alert-info border-0 shadow-sm mb-0">
                <h6 class="fw-bold mb-2"><i class="bi bi-calculator me-2"></i>Price Calculation</h6>
                <div class="d-flex justify-content-between mb-1 text-sm">
                  <span>Base Price ({{ bookingStore.pricePreview.duration_hours }} hrs):</span>
                  <span class="fw-bold">৳ {{ bookingStore.pricePreview.base_price }}</span>
                </div>
                <div v-for="rule in bookingStore.pricePreview.applied_rules" :key="rule.name" class="d-flex justify-content-between mb-1 text-sm text-primary">
                  <span>+ {{ rule.name }} ({{ rule.type }}):</span>
                  <span class="fw-bold">৳ {{ rule.modifier }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1 text-sm">
                  <span>Gross Total:</span>
                  <span class="fw-bold">৳ {{ bookingStore.pricePreview.total_price }}</span>
                </div>
                <div v-if="form.discount > 0" class="d-flex justify-content-between mb-1 text-sm text-danger">
                  <span>Discount:</span>
                  <span class="fw-bold">- ৳ {{ form.discount }}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between text-dark">
                  <span class="fw-bold">Net Amount:</span>
                  <span class="fw-bold fs-5">৳ {{ Math.max(0, bookingStore.pricePreview.total_price - (form.discount || 0)) }}</span>
                </div>
              </div>
            </div>

            <div class="col-12" v-if="bookingStore.priceError">
              <div class="alert alert-danger border-0 shadow-sm mb-0 text-sm">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ bookingStore.priceError }}
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold text-secondary">Discount (৳)</label>
              <input type="number" v-model="form.discount" class="form-control dark-input" min="0" :max="bookingStore.pricePreview?.total_price || 0">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold text-secondary">Paid Amount (৳)</label>
              <input type="number" v-model="form.paid_amount" class="form-control dark-input" min="0">
            </div>

            <div class="col-12 mt-4 text-end">
              <router-link to="/bookings" class="btn btn-light me-2">Cancel</router-link>
              <button type="submit" class="btn btn-primary px-4" :disabled="bookingStore.creating">
                <span v-if="bookingStore.creating" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle me-1"></i> Confirm Booking
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Client Create Modal -->
    <div v-if="showClientModal" class="modal-backdrop fade show"></div>
    <div v-if="showClientModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow" style="background: #1e1e1e;">
          <div class="modal-header" style="background: #2a2a2a; border-bottom: 1px solid #444;">
            <h5 class="modal-title fw-bold text-light"><i class="bi bi-person-plus me-2"></i>Add New Client</h5>
            <button type="button" class="btn-close btn-close-white" @click="closeClientModal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitClient">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-light">Client Name *</label>
                  <input type="text" v-model="clientForm.name" class="form-control dark-input" required>
                  <small class="text-danger" v-if="clientStore.errors.name">{{ clientStore.errors.name[0] }}</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-light">Phone Number *</label>
                  <input type="text" v-model="clientForm.phone" class="form-control dark-input" required>
                  <small class="text-danger" v-if="clientStore.errors.phone">{{ clientStore.errors.phone[0] }}</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-light">Email Address</label>
                  <input type="email" v-model="clientForm.email" class="form-control dark-input">
                  <small class="text-danger" v-if="clientStore.errors.email">{{ clientStore.errors.email[0] }}</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-light">Opening Due Amount (৳)</label>
                  <input type="number" v-model="clientForm.total_due" class="form-control dark-input">
                  <small class="text-danger" v-if="clientStore.errors.total_due">{{ clientStore.errors.total_due[0] }}</small>
                </div>
                <div class="col-12">
                  <label class="form-label text-light">Address</label>
                  <textarea v-model="clientForm.address" class="form-control dark-input" rows="3"></textarea>
                  <small class="text-danger" v-if="clientStore.errors.address">{{ clientStore.errors.address[0] }}</small>
                </div>
                <div class="col-12 mt-4 text-end">
                  <button type="button" class="btn btn-light me-2" @click="closeClientModal">Cancel</button>
                  <button type="submit" class="btn btn-primary px-4" :disabled="clientStore.loading">
                    <span v-if="clientStore.loading" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check-circle me-1"></i> Save Client
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Ground Create Modal -->
    <div v-if="showGroundModal" class="modal-backdrop fade show"></div>
    <div v-if="showGroundModal" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow" style="background: #1e1e1e;">
          <div class="modal-header" style="background: #2a2a2a; border-bottom: 1px solid #444;">
            <h5 class="modal-title fw-bold text-light"><i class="bi bi-plus-circle me-2"></i>Add New Ground</h5>
            <button type="button" class="btn-close btn-close-white" @click="closeGroundModal"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitGround">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-light">Ground Name *</label>
                  <input type="text" v-model="groundForm.name" class="form-control dark-input" required>
                  <small class="text-danger" v-if="groundStore.errors.name">{{ groundStore.errors.name[0] }}</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-light">Status *</label>
                  <select v-model="groundForm.status" class="form-select dark-input" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="maintenance">Maintenance</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-light">Base Price Per Hour (৳) *</label>
                  <input type="number" step="0.01" v-model="groundForm.base_price_per_hour" class="form-control dark-input" required>
                  <small class="text-danger" v-if="groundStore.errors.base_price_per_hour">{{ groundStore.errors.base_price_per_hour[0] }}</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-light">Location</label>
                  <input type="text" v-model="groundForm.location" class="form-control dark-input">
                  <small class="text-danger" v-if="groundStore.errors.location">{{ groundStore.errors.location[0] }}</small>
                </div>
                <div class="col-12">
                  <label class="form-label text-light">Description (Rich Text)</label>
                  <div class="bg-white rounded">
                    <QuillEditor v-model:content="groundForm.description" contentType="html" theme="snow" style="min-height: 150px; color: black;" />
                  </div>
                </div>
                <div class="col-12 mt-4 text-end">
                  <button type="button" class="btn btn-light me-2" @click="closeGroundModal">Cancel</button>
                  <button type="submit" class="btn btn-primary px-4" :disabled="groundStore.loading">
                    <span v-if="groundStore.loading" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check-circle me-1"></i> Save Ground
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Swal from 'sweetalert2';
import VueMultiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { useBookingStore } from '@/store/bookings';
import { useClientStore } from '@/store/clients';
import { useGroundStore } from '@/store/grounds';

const router = useRouter();
const route = useRoute();
const bookingStore = useBookingStore();
const clientStore = useClientStore();
const groundStore = useGroundStore();

const selectedClientObj = ref(null);
const selectedGroundObj = ref(null);
const showClientModal = ref(false);
const showGroundModal = ref(false);

const clientForm = ref({
  name: '',
  phone: '',
  email: '',
  total_due: 0,
  address: ''
});

const groundForm = ref({
  name: '',
  location: '',
  description: '',
  base_price_per_hour: 0,
  status: 'active'
});

const getBDTime = () => {
  const now = new Date();
  const parts = now.toLocaleString('en-US', { timeZone: 'Asia/Dhaka', hour12: false, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
  const match = parts.match(/(\d+)\/(\d+)\/(\d+),?\s*(\d+):(\d+)/);
  if (!match) return { h: 0, m: 0, date: new Date().toISOString().split('T')[0] };
  const [_, month, day, year, h, m] = match;
  return { h: parseInt(h), m: parseInt(m), date: `${year}-${month}-${day}` };
};

const todayStr = getBDTime().date;

const getNextSlot = () => {
  const { h, m } = getBDTime();
  let hours = h;
  let minutes = m;
  if (minutes > 0 && minutes <= 30) minutes = 30;
  else if (minutes > 30) { minutes = 0; hours += 1; }
  const start = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
  const endHours = (hours + 1) % 24;
  const end = `${String(endHours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
  return { start, end };
};

const defaultSlot = getNextSlot();

const form = ref({
  client_id: '',
  ground_id: route.query.ground_id || '',
  date: route.query.date || todayStr,
  start_time: route.query.start_time || defaultSlot.start,
  end_time: route.query.start_time
    ? `${String(parseInt(route.query.start_time.split(':')[0]) + 1).padStart(2, '0')}:${route.query.start_time.split(':')[1]}`
    : defaultSlot.end,
  discount: 0,
  paid_amount: 0
});

const minTime = computed(() => {
  if (form.value.date !== todayStr) return null;
  const { h, m } = getBDTime();
  let minH = h;
  let minM = m;
  if (minM > 0 && minM <= 30) minM = 30;
  else if (minM > 30) { minM = 0; minH += 1; }
  return `${String(minH).padStart(2, '0')}:${String(minM).padStart(2, '0')}`;
});

const clientLabel = (client) => {
  return `${client.name} (${client.phone})`;
};

const groundLabel = (ground) => {
  return ground.name;
};

watch(selectedClientObj, (newVal) => {
  form.value.client_id = newVal ? newVal.id : '';
});

watch(selectedGroundObj, (newVal) => {
  form.value.ground_id = newVal ? newVal.id : '';
  calculatePrice();
});

const onGroundSelect = () => {
  calculatePrice();
};

const onStartTimeChange = () => {
  if (form.value.start_time) {
    const [h, m] = form.value.start_time.split(':').map(Number);
    const endH = (h + 1) % 24;
    form.value.end_time = `${String(endH).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  }
  calculatePrice();
};

watch(() => form.value.start_time, (val) => {
  if (val) {
    const [h, m] = val.split(':').map(Number);
    const endH = (h + 1) % 24;
    form.value.end_time = `${String(endH).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  }
});

const onDateChange = () => {
  if (form.value.date === todayStr) {
    const slot = getNextSlot();
    form.value.start_time = slot.start;
    form.value.end_time = slot.end;
  }
  calculatePrice();
};

const calculatePrice = async () => {
  if (!form.value.ground_id || !form.value.date || !form.value.start_time || !form.value.end_time) {
    bookingStore.resetPrice();
    return;
  }
  if (form.value.date === todayStr && minTime.value && form.value.start_time < minTime.value) {
    bookingStore.priceError = 'Cannot select a past time for today.';
    bookingStore.pricePreview = null;
    return;
  }
  await bookingStore.calculatePrice({
    ground_id: form.value.ground_id,
    date: form.value.date,
    start_time: form.value.start_time,
    end_time: form.value.end_time
  });
};

const submitBooking = async () => {
  if (form.value.date === todayStr && minTime.value && form.value.start_time < minTime.value) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Cannot book a past time slot.', showConfirmButton: false, timer: 3000 });
    return;
  }
  const success = await bookingStore.createBooking(form.value);
  if (success) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Booking created successfully!', showConfirmButton: false, timer: 3000 });
    router.push('/bookings');
  } else {
    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: bookingStore.error, showConfirmButton: false, timer: 5000 });
  }
};

const openClientModal = () => {
  clientStore.errors = {};
  clientForm.value = { name: '', phone: '', email: '', total_due: 0, address: '' };
  showClientModal.value = true;
};

const closeClientModal = () => {
  showClientModal.value = false;
  clientStore.errors = {};
};

const submitClient = async () => {
  const success = await clientStore.createClient(clientForm.value);
  if (success) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Client added successfully!', showConfirmButton: false, timer: 3000 });
    await clientStore.fetchAllClients();
    const newClient = clientStore.allClients.find(c => c.phone === clientForm.value.phone);
    if (newClient) selectedClientObj.value = newClient;
    closeClientModal();
  }
};

const openGroundModal = () => {
  groundStore.errors = {};
  groundForm.value = { name: '', location: '', description: '', base_price_per_hour: 0, status: 'active' };
  showGroundModal.value = true;
};

const closeGroundModal = () => {
  showGroundModal.value = false;
  groundStore.errors = {};
};

const submitGround = async () => {
  const groundName = groundForm.value.name;
  const success = await groundStore.createGround(groundForm.value);
  if (success) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Ground added successfully!', showConfirmButton: false, timer: 3000 });
    await bookingStore.fetchGrounds();
    const newGround = bookingStore.activeGrounds.find(g => g.name === groundName);
    if (newGround) selectedGroundObj.value = newGround;
    closeGroundModal();
  }
};

onMounted(async () => {
  clientStore.fetchAllClients();
  await bookingStore.fetchGrounds();
  if (form.value.ground_id) {
    selectedGroundObj.value = bookingStore.activeGrounds.find(g => g.id == form.value.ground_id) || null;
    calculatePrice();
  }
});

onUnmounted(() => {
  bookingStore.resetPrice();
});
</script>

<style scoped>
.dark-input {
  background-color: #2a2a2a !important;
  color: #fff !important;
  border-color: #444 !important;
}
.dark-input:focus {
  background-color: #333 !important;
  color: #fff !important;
  border-color: #666 !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
.dark-input option {
  background-color: #2a2a2a !important;
  color: #fff !important;
}
input[type="date"].dark-input::-webkit-calendar-picker-indicator,
input[type="time"].dark-input::-webkit-calendar-picker-indicator {
  filter: invert(1);
}

.client-multiselect-wrap {
  flex: 1;
  min-width: 0;
}
.client-multiselect-wrap :deep(.multiselect) {
  min-height: 38px;
}

:deep(.dark-multiselect .multiselect) {
  background-color: #2a2a2a;
  border-color: #444;
  color: #fff;
  min-height: 38px;
}
:deep(.dark-multiselect .multiselect__single) {
  background-color: #2a2a2a;
  color: #fff;
}
:deep(.dark-multiselect .multiselect__input) {
  background-color: #2a2a2a;
  color: #fff;
  border: none;
  box-shadow: none;
}
:deep(.dark-multiselect .multiselect__placeholder) {
  color: #ffffff;
}
:deep(.dark-multiselect .multiselect__content-wrapper) {
  background-color: #2a2a2a;
  border-color: #444;
}
:deep(.dark-multiselect .multiselect__option) {
  color: #fff;
  background-color: #2a2a2a;
}
:deep(.dark-multiselect .multiselect__option--highlight) {
  background-color: #0d6efd;
  color: #fff;
}
:deep(.dark-multiselect .multiselect__option--selected) {
  background-color: #198754;
  color: #fff;
}
:deep(.dark-multiselect .multiselect__tags) {
  background-color: #2a2a2a;
  border-color: #444;
  color: #fff;
}
:deep(.dark-multiselect .multiselect__tag) {
  background-color: #0d6efd;
  color: #fff;
}
:deep(.dark-multiselect .multiselect__tag-icon) {
  color: #fff;
}
:deep(.dark-multiselect .multiselect__tag-icon:hover) {
  color: #fff;
  background-color: rgba(255,255,255,0.2);
}
:deep(.dark-multiselect .multiselect__clear) {
  color: #888;
}
:deep(.dark-multiselect .multiselect--active .multiselect__tags) {
  border-color: #666;
}
</style>
