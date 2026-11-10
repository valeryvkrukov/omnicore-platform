<script setup>
import { ref } from 'vue';
import { useTenantStore } from './store/tenant';
import AnalyticsDashboardView from './views/AnalyticsDashboardView.vue';
import CustomerListView from './views/CustomerListView.vue';

const tenantStore = useTenantStore();
const currentView = ref('analytics');
</script>

<template>
  <div class="flex h-screen bg-[#0b0f17] text-gray-200 font-sans overflow-hidden">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#111827]/60 border-r border-gray-800 flex flex-col justify-between">
      <div class="p-6">
        <div class="flex items-center gap-3 mb-8">
          <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white shadow-[0_0_15px_rgba(79,70,229,0.5)]">Ω</div>
          <span class="font-bold tracking-wider text-gray-100 text-md uppercase">OmniCore</span>
        </div>

        <nav class="space-y-2">
          <button
            @click="currentView = 'analytics'"
            :class="[
              'w-full flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition border',
              currentView === 'analytics'
                ? 'bg-indigo-600/10 border-indigo-500/30 text-indigo-400'
                : 'bg-transparent border-transparent text-gray-400 hover:text-gray-200 hover:bg-gray-800/30'
            ]"
          >
            📊 Analytics Dashboard
          </button>
          <button
            @click="currentView = 'customers'"
            :class="[
            'w-full flex items-center px-4 py-2.5 text-sm font-medium rounded-xl transition border',
            currentView === 'customers'
              ? 'bg-indigo-600/10 border-indigo-500/30 text-indigo-400'
              : 'bg-transparent border-transparent text-gray-400 hover:text-gray-200 hover:bg-gray-800/30'
            ]"
          >
            👥 Clients Registry
          </button>
        </nav>
      </div>
      <div class="p-6 border-t border-gray-800/60 text-center text-xs text-gray-600 tracking-wider">
        v8.1.0-alpha
      </div>
    </aside>

    <!-- MAIN CONTENTS -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- MAIN TOPBAR -->
      <header class="h-16 bg-[#111827]/40 backdrop-blur-md border-b border-gray-800 px-8 flex items-center justify-between">
        <div class="text-sm font-medium text-gray-400">
          Active area: <span class="text-gray-200 font-semibold ml-1">{{ tenantStore.tenantName }}</span>
        </div>

        <!-- TENANTS SWITCH SELECTOR (Multitenant support) -->
        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-500 uppercase tracking-wider mr-1">Tenant Space</span>
          <select
            :value="tenantStore.currentTenantId"
            @change="tenantStore.selectTenant($event.target.value)"
            class="bg-[#1f2937]/80 border border-gray-700 text-gray-200 text-xs font-semibold rounded-xl px-3 py-1.5 focus:outline-none focus:border-indigo-500 transition cursor-pointer"
          >
            <option v-for="t in tenantStore.availableTenants" :key="t.id" :value="t.id">
              {{ t.name }}
            </option>
          </select>
        </div>
      </header>

      <!-- DYNAMIC SCREEN -->
      <main class="flex-1 overflow-y-auto p-8 bg-transparent">
        <div class="max-w-8xl mx-auto">
          <AnalyticsDashboardView v-if="currentView === 'analytics'" />
          <CustomerListView v-if="currentView === 'customers'" />
        </div>
      </main>

    </div>
  </div>
</template>
