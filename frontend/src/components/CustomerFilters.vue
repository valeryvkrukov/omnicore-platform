<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useTenantStore } from '../store/tenant';

const emit = defineEmits(['filter-changed']);
const tenantStore = useTenantStore();

// Dynamic lists loaded from backend
const availableCities = ref([]);
const availableTags = ref([]);
const isLoadingMeta = ref(false);

// Reactive state of selected filters
const activeFilters = reactive({
  city: '',
  age: null,
  tag: ''
});

// Loading accessible cities and tags from JSONB-attributes
const fetchMetadata = async () => {
  if (!tenantStore.currentTenantId) return;

  isLoadingMeta.value = true;
  try {
    const response = await fetch('/api/v1/customers/filter-meta', {
      method: 'GET',
      headers: tenantStore.apiHeaders
    });

    if (!response.ok) {
      console.log(`Failed to fetch filter metadata: ${response.status}`);
      return;
    }

    const data = await response.json();
    availableCities.value = data.cities || [];
    availableTags.value = data.tags || [];
  } catch (error) {
    console.error('Metadata Synchronization Error:', error);
  } finally {
    isLoadingMeta.value = false;
  }
}

const applyFilters = () => {
  emit('filter-changed', { ...activeFilters });
};

const toggleTag = (tag) => {
  activeFilters.tag = activeFilters.tag === tag ? '' : tag;
  applyFilters();
};

const resetFilters = () => {
  activeFilters.city = '';
  activeFilters.age = null;
  activeFilters.tag = '';
  applyFilters();
};

// Watch for tenant change: if tenant changed, update metadata
watch(() => tenantStore.currentTenantId, () => {
  resetFilters();
  fetchMetadata();
});

onMounted(() => {
  fetchMetadata();
});
</script>

<template>
  <div class="p-5 bg-[#111827]/40 backdrop-blur-md border border-gray-800 rounded-xl shadow-2xl mb-6">
    <div class="flex flex-wrap gap-6 items-center justify-between">
      <div class="flex flex-wrap gap-4 items-center">
        <!-- Cities filter -->
        <div class="flex flex-col">
          <span class="text-[11px] font-medium tracking-wider text-gray-400 uppercase mb-1.5">Location (JSONB)</span>
          <select
            v-model="activeFilters.city"
            @change="applyFilters"
            :disabled="isLoadingMeta"
            class="bg-[#1f2937]/60 border border-gray-700 text-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition disabled:opacity-50"
          >
            <option value="">{{ isLoadingMeta ? 'Synchronizing...' : 'All locations' }}</option>
            <option v-for="city in availableCities" :key="city" :value="city">
              {{ city }}
            </option>
          </select>
        </div>

        <!-- Age filter -->
        <div class="flex flex-col">
          <span class="text-[11px] font-medium tracking-wider text-gray-400 uppercase mb-1.5">Age</span>
          <input
            type="number"
            v-model.number="activeFilters.age"
            @input="applyFilters"
            placeholder="For ex., 25"
            class="bg-[#1f2937]/60 border border-gray-700 text-gray-200 rounded-lg px-3 py-1.5 text-sm w-28 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
          />
        </div>

        <!-- Tags filter -->
        <div class="flex flex-col">
          <span class="text-[11px] font-medium tracking-wider text-gray-400 uppercase mb-1.5">Quick Tags</span>
          <div class="flex flex-wrap gap-2">
            <span v-if="isLoadingMeta" class="text-xs text-gray-500 py-1.5">Loading tags...</span>
            <span v-else-if="availableTags.length === 0" class="text-xs text-gray-500 py-1.5">No active tags</span>
            <button
              v-else
              v-for="tag in availableTags"
              :key="tag"
              @click="toggleTag(tag)"
              :class="[
                'px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-200',
                activeFilters.tag === tag
                  ? 'bg-indigo-600/30 border-indigo-500 text-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.2)]'
                  : 'bg-[#1f2937]/40 border-gray-700 text-gray-400 hover:border-gray-600 hover:text-gray-300'
              ]"
            >
              #{{ tag }}
            </button>
          </div>
        </div>
      </div>

      <!-- Reset button -->
      <button
          v-if="activeFilters.city || activeFilters.age || activeFilters.tag"
          @click="resetFilters"
          class="text-xs font-medium text-rose-400 hover:text-rose-300 transition underline underline-offset-4"
      >
        Reset filters
      </button>
    </div>
  </div>
</template>
