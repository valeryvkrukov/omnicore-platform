<script setup>
import { ref, onMounted, watch } from 'vue';
import { useTenantStore } from '../store/tenant';
import CustomerFilters from '../components/CustomerFilters.vue';
import CustomerTable from '../components/CustomerTable.vue';
import AnalyticsChart from '../components/AnalyticsChart.vue';

const tenantStore = useTenantStore();
const chartData = ref({ labels: [], data: [] });
const metrics = ref({ total_customers: 0, current_plan: 'free' });
const customers = ref([]);
const isLoading = ref(false);

const loadDashboardData = async (filters = {}) => {
  isLoading.value = true;

  try {
    // 1. Load analytics for graph
    const analyticsRes = await fetch('/api/v1/customers/analytics', {
      headers: tenantStore.apiHeaders
    });

    const analyticsData = await analyticsRes.json();

    chartData.value = analyticsData.chart;
    metrics.value = analyticsData.metrics;

    // 2. Update synchronously list of clients by JSONB filters selected in UI
    const params = new URLSearchParams();
    if (filters.city) params.append('city', filters.city);
    if (filters.age) params.append('age', filters.age);
    if (filters.tag) params.append('tag', filters.tag);

    const customersRes = await fetch(`/api/v1/customers?${params.toString()}`, {
      headers: tenantStore.apiHeaders
    });
    customers.value = await customersRes.json();
  } catch (error) {
    console.error('Dashboard synchronization failed:', error);
  } finally {
    isLoading.value = false;
  }
};

watch(() => tenantStore.currentTenantId, () => {
  loadDashboardData();
});

onMounted(() => {
  loadDashboardData();
});
</script>

<template>
  <div class="space-y-6">
    <!-- TOP METRICS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="p-5 bg-[#111827]/40 border border-gray-800 rounded-xl shadow-lg">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Clients Total</span>
        <div class="text-2xl font-bold text-gray-100 mt-2">{{ metrics.total_customers }}</div>
      </div>
      <div class="p-5 bg-[#111827]/40 border border-gray-800 rounded-xl shadow-lg">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Current Tariff</span>
        <div class="text-2xl font-bold text-indigo-400 mt-2 uppercase tracking-wide">{{ metrics.current_plan }}</div>
      </div>
      <div class="p-5 bg-[#111827]/40 border border-gray-800 rounded-xl shadow-lg">
        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">API queries limit</span>
        <div class="text-2xl font-bold text-indigo-400 mt-2 uppercase tracking-wide">{{ metrics.rate_limit ?? 5 }} rps</div>
      </div>
    </div>

    <!-- ANALYTICS CHART -->
    <div class="p-5 bg-[#111827]/40 border border-gray-800 rounded-xl shadow-lg">
      <h2 class="text-sm font-semibold text-gray-400 mb-4 uppercase tracking-wider">Registrations chronology (last week)</h2>
      <AnalyticsChart :chart-values="chartData" />
    </div>

    <!-- INTERACTIVE FILTER -->
    <CustomerFilters @filter-changed="loadDashboardData" />

    <!-- CUSTOMER TABLE -->
    <CustomerTable :customers="customers" :is-loading="isLoading" />
  </div>
</template>