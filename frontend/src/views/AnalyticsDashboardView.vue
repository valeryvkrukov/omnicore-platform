<script setup>
import { ref, onMounted, watch } from 'vue'
import { useTenantStore } from '../store/tenant'
import { Line } from 'vue-chartjs'

import 'chart.js/auto'

const tenantStore = useTenantStore()
const loading = ref(false)
const metrics = ref({})

const chartData = ref({
  labels: [],
  datasets: []
})

// Chart display options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#94A3B8', font: { family: 'monospace', size: 11 } }
    },
    y: {
      grid: { color: 'rgba(30, 41, 59, 0.5)' },
      ticks: { color: '#94A3B8', font: { family: 'monospace', size: 11 }, precision: 0 }
    }
  }
}

const loadAnalytics = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/v1/customers/analytics', {
      method: 'GET',
      headers: tenantStore.apiHeaders
    })

    if (!response.ok) throw new Error('API Error')

    const data = await response.json()
    metrics.value = data.metrics

    // Setup glow and gradients for chart
    chartData.value = {
      labels: data.chart.labels,
      datasets: [{
        label: 'Registrations',
        data: data.chart.data,
        fill: true,
        borderColor: '#6366F1',
        borderWidth: 2,
        pointBackgroundColor: '#818CF8',
        pointHoverRadius: 6,
        backgroundColor: (context) => {
          const ctx = context.chart.ctx
          const gradient = ctx.createLinearGradient(0, 0, 0, 300)
          gradient.addColorStop(0, 'rgba(99, 102, 241, 0.25)')
          gradient.addColorStop(1, 'rgba(99, 102, 241, 0)')
          return gradient
        },
        tension: 0.35
      }]
    }
  } catch (error) {
    console.error('Error loading data:', error)
  } finally {
    loading.value = false
  }
}

// Automatically reload data when user changes the tenant
watch(() => tenantStore.currentTenantId, () => {
  loadAnalytics()
})

onMounted(() => {
  loadAnalytics()
})
</script>

<template>
  <div class="space-y-8">
    <!-- ANALYTICS DASHBOARD -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-[#0B0F19]/60 border border-slate-800/80 rounded-xl p-6 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-500/5 blur-3xl rounded-full"></div>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total clients (7 days)</h3>
        <p class="text-4xl font-extrabold text-white tracking-tight mt-2.5">
          {{ metrics.total_customers ?? '...' }}
        </p>
        <div class="text-[11px] text-emerald-400 font-medium mt-2">Isolation: TenantFilter Active</div>
      </div>
      <div class="bg-[#0B0F19]/60 border border-slate-800/80 rounded-xl p-6">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Plan: SaaS</h3>
        <p class="text-4xl font-extrabold text-indigo-400 uppercase tracking-tight mt-2.5">
          {{ metrics.current_plan ?? '...' }}
        </p>
        <div class="text-[11px] text-slate-500 font-medium mt-2">Context: PostgreSQL Metadata</div>
      </div>
      <div class="bg-[#0B0F19]/60 border border-slate-800/80 rounded-xl p-6">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">API queries limit</h3>
        <p class="text-4xl font-extrabold text-slate-200 tracking-tight mt-2.5">
          {{ metrics.rate_limit ?? 5 }} rps
        </p>
        <div class="text-[11px] text-slate-500 font-medium mt-2">Component: Symfony RateLimiter</div>
      </div>
    </section>

    <!-- Graphs panel -->
    <div class="bg-[#0B0F19]/60 border border-slate-800/80 rounded-xl p-6 backdrop-blur-md space-y-4">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-lg font-bold text-white">Customer Registration Chronology</h3>
          <p class="text-xs text-slate-400 mt-0.5">Dynamics of incoming webhooks, aggregated by day</p>
        </div>
        <button @click="loadAnalytics" class="text-xs font-semibold bg-slate-900 border border-slate-800 hover:border-indigo-500/50 text-slate-300 px-3 py-1.5 rounded-lg transition-all">
          🔄 Обновить
        </button>
      </div>

      <!-- Graph container -->
      <div class="h-80 w-full relative">
        <div v-if="loading" class="absolute inset-0 flex items-center justify-between text-sm text-slate-400 font-medium bg-[#0B0F19]/10 backdrop-blur-xs z-10 justify-center">
          Loading metrics from the Symfony API...
        </div>

        <Line
            v-if="chartData.labels.length > 0"
            :data="chartData"
            :options="chartOptions"
        />

        <div v-else-if="!loading" class="absolute inset-0 flex items-center justify-between text-sm text-slate-500 font-medium justify-center">
          There is no data available to generate a graph for the last 7 days.
        </div>
      </div>
    </div>
  </div>
</template>