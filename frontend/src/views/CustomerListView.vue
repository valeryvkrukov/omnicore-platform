<script setup>
import { ref, onMounted, watch } from 'vue';
import { useTenantStore } from '../store/tenant';
import CustomerFilters from '../components/CustomerFilters.vue';
import CustomerTable from '../components/CustomerTable.vue';

const tenantStore = useTenantStore();
const customers = ref([]);
const isLoading = ref(false);

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
    </div>

    <!-- Filters component -->
    <CustomerFilters @filter-changed="fetchCustomers" />

    <!-- Customer table -->
    <CustomerTable :customers="customers" :is-loading="isLoading" />
  </div>
</template>
