<script setup>
import { ref, reactive } from 'vue';
import { useTenantStore } from '../store/tenant';

const props = defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close', 'created']);
const tenantStore = useTenantStore();

const isSubmitting = ref(false);
const errorMessage = ref('');

const form = reactive({
  email: '',
  city: '',
  age: null,
  tags: ''
});

const closeModal = () => {
  form.email = '';
  form.city = '';
  form.age = null;
  form.tags = '';
  errorMessage.value = '';
  emit('close');
};

const submitForm = async () => {
  if (!form.email) {
    errorMessage.value = 'Email is a required field';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const tagsArray = form.tags
        ? form.tags.split(',').map(t => t.trim()).filter(t => t !== '')
        : [];

    const payload = {
      email: form.email,
      properties: {
        city: form.city ? form.city.trim() : null,
        age: form.age ? parseInt(form.age, 10) : null,
        tags: tagsArray
      }
    };

    const response = await fetch('/api/v1/customers', {
      method: 'POST',
      headers: tenantStore.apiHeaders,
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      const errorData = await response.json();
      console.log(errorData.message || 'Client is not saved...');
      return;
    }

    emit('created');
    closeModal();
  } catch (error) {
    errorMessage.value = error.message;
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <Transition name="modal-fade">
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-[#070a0f]/80 backdrop-blur-sm" @click="closeModal"></div>
      <div class="relative w-full max-w-md bg-[#111827] border border-gray-800 rounded-2xl shadow-2xl p-6 overflow-hidden">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-md font-bold tracking-wider text-gray-100 uppercase">➕ Add client to registry</h3>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-300 text-lg transition">&times;</button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <!-- Global error message -->
          <div v-if="errorMessage" class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-xs text-rose-400">
            ⚠️ {{ errorMessage }}
          </div>

          <!-- Email field -->
          <div class="flex flex-col">
            <label class="text-[11px] font-medium tracking-wider text-gray-400 uppercase mb-1.5">Email *</label>
            <input
              type="email"
              v-model="form.email"
              required
              placeholder="name@company.com"
              class="bg-[#1f2937]/60 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 transition"
            />
          </div>

          <div class="border-t border-gray-800/60 my-4 pt-2">
            <span class="text-[10px] font-semibold tracking-widest text-indigo-400 uppercase">Custom attributes (JSONB)</span>
          </div>

          <!-- JSONB: city -->
          <div class="flex flex-col">
            <label class="text-[11px] font-medium tracking-wider text-gray-400 uppercase mb-1.5">Location / City</label>
            <input
              type="text"
              v-model="form.city"
              placeholder="For ex., Kharkiv"
              class="bg-[#1f2937]/60 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 transition"
            />
          </div>

          <!-- JSONB: age -->
          <div class="flex flex-col">
            <label class="text-[11px] font-medium tracking-wider text-gray-400 uppercase mb-1.5">Age</label>
            <input
              type="text"
              v-model.number="form.age"
              placeholder="25"
              class="bg-[#1f2937]/60 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 transition"
            />
          </div>

          <!-- JSONB: tags -->
          <div class="flex flex-col">
            <label class="text-[11px] font-medium tracking-wider text-gray-400 uppercase mb-1.5">Quick Tags</label>
            <input
              type="text"
              v-model="form.tags"
              placeholder="VIP, New, Bestseller, ..."
              class="bg-[#1f2937]/60 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-500 transition"
            />
          </div>

          <!-- Buttons -->
          <div class="flex items-center justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-xs font-semibold text-gray-400 hover:text-gray-200 transition"
            >
              Cancel
            </button>
            <button
                type="submit"
                :disabled="isSubmitting"
                class="px-4 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-[0_0_15px_rgba(79,70,229,0.3)] transition disabled:opacity-50"
            >
              {{ isSubmitting ? 'Saving...' : 'Create Client' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>