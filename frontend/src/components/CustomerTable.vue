<script setup>
defineProps({
  customers: {
    type: Array,
    required: true,
    default: () => []
  },
  isLoading: {
    type: Boolean,
    default: false
  }
});
</script>

<template>
  <div class="bg-[#111827]/40 border border-gray-800 rounded-xl shadow-2xl overflow-hidden">
    <div v-if="isLoading" class="p-8 text-center text-gray-500 text-sm tracking-wide">
      Synchronizing OmniCore repository data...
    </div>

    <div v-else-if="customers.length === 0" class="p-8 text-center text-gray-500 text-sm">
      No data for the selected filters.
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-800">
        <thead class="bg-[#1f2937]/20">
          <tr>
            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Tenant's Email</th>
            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Location (JSONB)</th>
            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Age</th>
            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Tags</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-800 bg-transparent">
          <tr v-for="customer in customers" :key="customer.id" class="hover:bg-[#1f2937]/10 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-200">
              {{ customer.email }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
              {{ customer.properties?.city || '—' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
              {{ customer.properties?.age ? `${customer.properties.age} years` : '—' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
              <div v-if="Array.isArray(customer.properties?.tags) && customer.properties.tags.length > 0" class="flex flex-wrap gap-1.5">
                <span
                  v-for="tag in customer.properties.tags"
                  :key="tag"
                  class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20"
                >
                  {{ tag }}
                </span>
              </div>
              <span
                v-else-if="typeof customer.properties?.tags === 'string' && customer.properties.tags !== ''"
                class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20"
              >
                {{ customer.properties.tags }}
              </span>
              <span v-else>—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
