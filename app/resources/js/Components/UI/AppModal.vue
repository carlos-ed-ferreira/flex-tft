<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div
          class="bg-gray-900 border border-gray-700 rounded-xl flex flex-col w-full"
          :class="maxWidthClass"
          :style="maxHeight ? { maxHeight } : {}"
        >
          <!-- Header -->
          <div
            v-if="title || $slots.header"
            class="flex items-center justify-between px-5 pt-5 pb-3 flex-shrink-0"
          >
            <slot name="header">
              <h3 class="text-lg font-semibold text-white">{{ title }}</h3>
            </slot>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-white transition"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Body -->
          <div class="px-5 pb-5 flex-1 overflow-y-auto min-h-0">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="px-5 pb-5 flex-shrink-0">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { watch, onUnmounted } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: '' },
  maxWidth: { type: String, default: 'lg' },
  maxHeight: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const maxWidthClass =
  {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
  }[props.maxWidth] || 'max-w-lg';

function handleEsc(e) {
  if (e.key === 'Escape') emit('close');
}

watch(
  () => props.show,
  (open) => {
    if (open) {
      document.addEventListener('keydown', handleEsc);
    } else {
      document.removeEventListener('keydown', handleEsc);
    }
  },
  { immediate: true },
);

onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc);
});
</script>
