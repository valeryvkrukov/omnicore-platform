<script setup>
import { useTenantStore } from './store/tenant'
import AnalyticsDashboardView from './views/AnalyticsDashboardView.vue'
import CustomerListView from "./views/CustomerListView.vue";

const tenantStore = useTenantStore()
</script>

<template>
  <div class="min-h-screen bg-[#060814] bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-slate-900/50 via-[#060814] to-[#060814] font-sans text-slate-200 flex flex-col md:flex-row">
    <!-- SIDEBAR -->
    <aside class="w-full md:w-80 bg-[#0B0F19]/80 backdrop-blur-xl border-b md:border-b-0 md:border-r border-slate-800/80 p-6 flex flex-col justify-between z-10">
      <div>
        <!-- LOGO -->
        <div class="relative group mb-10">
          <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg blur opacity-30 group-hover:opacity-50 transition duration-300"></div>
          <div class="relative bg-[#111827] border border-slate-800 rounded-lg p-5">
            <h1 class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
              OMNICORE
            </h1>
            <p class="text-xs font-semibold text-indigo-400/80 uppercase tracking-widest mt-1">B2B SaaS Engine</p>
          </div>
        </div>

        <!-- Tenant selector -->
        <div class="mb-10">
          <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Active Tenant:</label>
          <div class="relative">
            <select
                v-model="tenantStore.currentTenantId"
                @change="tenantStore.selectTenant(tenantStore.currentTenantId)"
                class="w-full appearance-none border border-slate-800 bg-[#111827] text-sm text-slate-200 font-medium rounded-lg p-3.5 pr-10 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all cursor-pointer"
            >
              <option v-for="t in tenantStore.availableTenants" :key="t.id" :value="t.id">
                {{ t.name }}
              </option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
              ▼
            </div>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="space-y-2">
          <a href="#" class="flex items-center space-x-3 bg-gradient-to-r from-indigo-600/20 to-transparent border-l-2 border-indigo-500 px-4 py-3 rounded-r-lg text-sm font-semibold text-white transition-all">
            <span class="text-indigo-400 text-lg">📊</span>
            <span>DASHBOARD / ANALYTICS</span>
          </a>
          <a href="#" class="flex items-center space-x-3 text-slate-400 hover:text-slate-200 hover:bg-slate-900/50 px-4 py-3 rounded-lg text-sm font-semibold transition-all">
            <span class="text-lg">⚡</span>
            <span>WEBHOOKS LOG</span>
          </a>
          <a href="#" class="flex items-center space-x-3 text-slate-400 hover:text-slate-200 hover:bg-slate-900/50 px-4 py-3 rounded-lg text-sm font-semibold transition-all">
            <span class="text-lg">👥</span>
            <span>CLIENTS (JSONB)</span>
          </a>
        </nav>
      </div>

      <!-- System status of backend containers -->
      <div class="mt-8 border border-slate-800/80 bg-[#111827]/60 rounded-xl p-4 space-y-3 text-xs font-medium text-slate-400 shadow-inner">
        <div class="flex items-center justify-between border-b border-slate-800/40 pb-2">
          <span class="text-slate-500 uppercase tracking-wider text-[10px] font-bold">Infrastructure</span>
          <span class="flex items-center text-emerald-400 font-semibold tracking-wide">
            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse mr-2"></span>
            ONLINE
          </span>
        </div>
        <div class="space-y-1.5 font-mono text-[11px] text-slate-400">
          <div class="flex justify-between">
            <span class="text-slate-600">Core:</span>
            <span class="text-slate-300">Symfony 8.1</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">DB:</span>
            <span class="text-slate-300">PostgreSQL 16</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-600">QUEUE:</span>
            <span class="text-indigo-400">Messenger</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6 md:p-10 space-y-8 overflow-y-auto">
      <!-- Top status bar -->
      <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-[#0B0F19]/40 border border-slate-800/60 rounded-xl p-6 backdrop-blur-md relative overflow-hidden group">
        <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-indigo-500/20 to-transparent"></div>
        <div>
          <h2 class="text-xl font-bold tracking-tight text-white">Data Infrastructure Management</h2>
          <p class="text-xs font-medium text-slate-400 mt-1">
            Environment: <span class="underline">Sandbox / Development</span>
          </p>
        </div>
        <div class="mt-4 sm:mt-0 font-mono text-[11px] bg-slate-900/90 border border-slate-800 text-indigo-300 px-3.5 py-2 rounded-lg shadow-inner flex items-center space-x-2">
          <span class="text-slate-600">Token:</span>
          <span class="font-bold tracking-wider text-slate-200">{{ tenantStore.currentTenantId.substring(0, 8) }}...</span>
        </div>
      </header>

      <AnalyticsDashboardView />
      <CustomerListView class="mt-8" />

    </main>
  </div>
</template>
