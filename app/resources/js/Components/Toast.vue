<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-if="visible"
        class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 px-4 py-3 rounded-lg shadow-2xl border max-w-sm"
        :class="typeClasses"
      >
        <!-- Icon -->
        <div class="flex-shrink-0">
          <CheckCircleIcon v-if="type === 'success'" class="w-5 h-5" />
          <XCircleIcon v-else-if="type === 'error'" class="w-5 h-5" />
          <InformationCircleIcon v-else class="w-5 h-5" />
        </div>

        <!-- Message -->
        <p class="text-sm font-medium">{{ message }}</p>

        <!-- Close button -->
        <button
          @click="close"
          class="flex-shrink-0 ml-auto opacity-70 hover:opacity-100 transition"
        >
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
  CheckCircleIcon,
  XCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/20/solid';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');
let timeoutId = null;

const typeClasses = computed(() => {
  const base = {
    success: 'bg-green-900/90 border-green-700 text-green-100',
    error: 'bg-red-900/90 border-red-700 text-red-100',
    info: 'bg-blue-900/90 border-blue-700 text-blue-100',
  };
  return base[type.value] || base.info;
});

function show(msg, toastType = 'success', duration = 4000) {
  if (timeoutId) clearTimeout(timeoutId);

  message.value = msg;
  type.value = toastType;
  visible.value = true;

  timeoutId = setTimeout(() => {
    close();
  }, duration);
}

function close() {
  visible.value = false;
  if (timeoutId) {
    clearTimeout(timeoutId);
    timeoutId = null;
  }
}

// Watch for flash messages from Laravel
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      show(flash.success, 'success');
    } else if (flash?.error) {
      show(flash.error, 'error');
    } else if (flash?.info) {
      show(flash.info, 'info');
    }
  },
  { deep: true, immediate: true },
);

defineExpose({ show, close });
</script>
