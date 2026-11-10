<script setup>
import { ref, onMounted, watch } from 'vue';
import { useTenantStore } from '../store/tenant';
import CustomerFilters from '../components/CustomerFilters.vue';
import CustomerTable from '../components/CustomerTable.vue';
import CreateCustomerModal from '../components/CreateCustomerModal.vue';

const tenantStore = useTenantStore();
const customers = ref([]);
const isLoading = ref(false);
const isModalOpen = ref(false);

let currentFilters = {};

const fetchCustomers = async (filters = currentFilters) => {
  currentFilters = filters;
  isLoading.value = true;

  try {
    const params = new URLSearchParams();

    if (filters.city) params.append('city', filters.city);
    if (filters.age) params.append('age', filters.age);
    if (filters.tag) params.append('tag', filters.tag);

    const response = await fetch(`/api/v1/customers?${params.toString()}`, {
      method: 'GET',
      headers: tenantStore.apiHeaders
    });

    if (!response.ok) {
      console.error(`API returned status: ${response.status}`);
      return;
    }

    customers.value = await response.json();
  } catch (error) {
    console.error('Error loading customers list:', error);
  } finally {
    isLoading.value = false;
  }
};

watch(() => tenantStore.currentTenantId, () => {
  fetchCustomers({});
});

onMounted(() => {
  fetchCustomers();
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold tracking-tight text-gray-100">Platform client registry</h1>
      <!-- Add customer button -->
      <button
        @click="isModalOpen = true"
        class="px-4 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-[0_0_15px_rgba(79,70,229,0.3)] transition"
      >
        ➕ New Customer
      </button>
    </div>

    <!-- Filters component -->
    <CustomerFilters @filter-changed="fetchCustomers" />

    <!-- Customer table -->
    <CustomerTable :customers="customers" :is-loading="isLoading" />

    <!-- Create customer modal -->
    <CreateCustomerModal
      :is-open="isModalOpen"
      @close="isModalOpen = false"
      @created="fetchCustomers"
    />
  </div>
</template>
