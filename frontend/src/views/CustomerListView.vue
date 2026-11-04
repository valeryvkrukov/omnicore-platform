<script setup>
import { ref, onMounted, watch } from 'vue'
import { useTenantStore } from '../store/tenant'

const tenantStore = useTenantStore()
const customers = ref([])
const loading = ref(false)

const loadCustomers = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/v1/customers', {
      method: 'GET',
      headers: tenantStore.apiHeaders
    })

    if (!response.ok) throw new Error('API Error')

    customers.value = await response.json()
  } catch (error) {
    console.error('Error retrieving the list of customers:', error)
  } {
    loading.value = false
  }
}

// Formatting complex JSONB values (arrays, objects) for output in tag
const formatJsonValue = (value) => {
  if (Array.isArray(value)) return `[${value.join(', ')}]`
  if (typeof value === 'object' && value !== null) return JSON.stringify(value)
  return value
}

const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Reload customers list when tenant changes
watch(() => tenantStore.currentTenantId, () => {
  loadCustomers()
})

onMounted(() => {
  loadCustomers()
})
</script>

<template>
  <div class="bg-[#0B0F19]/60 border border-slate-800/80 rounded-xl p-6 backdrop-blur-md space-y-6">
    <!-- Header and utilities -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h3 class="text-lg font-bold text-white">Platform client registry</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          Dynamic entity retrieval. Database-level filtering via <span class="text-indigo-400 underline">TenantFilter</span>/
        </p>
      </div>
      <button
          @click="loadCustomers"
          class="text-xs font-semibold bg-slate-900 border border-slate-800 hover:border-indigo-500/50 text-slate-300 px-4 py-2 rounded-lg transition-all active:scale-95"
      >
        🔄 Обновить список
      </button>
    </div>

    <!-- Loading indicator -->
    <div v-if="loading" class="py-20 text-center text-sm text-slate-400 font-mono tracking-wide animate-pulse">
      Executing an isolated SQL query against PostgreSQL...
    </div>

    <!-- Customers list -->
    <div v-else-if="customers.length > 0" class="overflow-x-auto border border-slate-800/60 rounded-lg">
      <table class="w-full text-left border-collapse font-sans">
        <thead>
          <tr class="bg-slate-900/50 border-b border-slate-800 text-xs font-bold text-slate-400 uppercase tracking-wider">
            <th class="p-4 w-12">ID (UUIDv7)</th>
            <th class="p-4 w-1/4">Email</th>
            <th class="p-4">Dynamic properties (PostgreSQL JSONB)</th>
            <th class="p-4 w-40 text-right">Date of creation</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/40 text-sm">
          <tr
            v-for="customer in customers"
            :key="customer.id"
            class="hover:bg-slate-900/30 transition-colors"
          >
            <!-- Short UUIDv7 -->
            <td class="p-4 font-mono text-xs text-indigo-400 font-medium">
              {{ customer.id.substring(0, 8) }}
            </td>

            <!-- Email -->
            <td class="p-4 text-slate-200 font-semibold tracking-tight">
              {{ customer.email ?? '—' }}
            </td>

            <!-- Dynamic properties -->
            <td class="p-4">
              <div class="flex flex-col gap-2">
                <!-- If have no props -->
                <span v-if="Object.keys(customer.properties).length === 0" class="text-xs font-mono text-slate-600 italic">
                  empty jsonb object
                </span>
                <!-- List key-value pairs -->
                <div
                    v-for="(value, key) in customer.properties"
                    :key="key"
                    class="flex items-center text-xs font-mono bg-slate-900/80 border border-slate-800 rounded-md overflow-hidden"
                >
                  <span class="bg-slate-800 px-2 py-1 text-slate-400 border-r border-slate-800/60">
                    {{ key }}
                  </span>
                  <span class="px-2 py-1 text-indigo-300 font-bold">
                    {{ formatJsonValue(value) }}
                  </span>
                </div>
              </div>
            </td>
            <!-- Date -->
            <td class="p-4 text-right font-mono text-xs text-slate-400">
              {{ formatDate(customer.createdAt) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- No customers message -->
    <div v-else class="border border-dashed border-slate-800 rounded-xl p-12 text-center space-y-2">
      <div class="text-2xl">👥</div>
      <h4 class="text-sm font-bold text-slate-300">The customer base is empty</h4>
      <p class="text-xs text-slate-500 max-w-sm mx-auto">
        The tenant has not yet sent any incoming webhooks, or the Symfony Messenger workers are in an idle state.
      </p>
    </div>
  </div>
</template>

<style scoped>

</style>