<template>
  <div class="relative w-full">
    <input
      ref="inputRef"
      :value="modelValue"
      v-bind="inputAttrs"
      :type="inputType"
      :class="inputClasses"
      @input="handleInput"
      @click="captureSelection"
      @focus="captureSelection"
      @keyup="captureSelection"
      @select="captureSelection"
    />

    <button
      v-if="isPasswordField"
      type="button"
      class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-gray-400 transition-colors hover:text-gray-200 focus:text-gray-200 focus:outline-none"
      :aria-label="passwordToggleLabel"
      :aria-pressed="isPasswordVisible"
      @pointerdown.prevent="captureSelection"
      @click="togglePasswordVisibility"
    >
      <EyeSlashIcon v-if="isPasswordVisible" class="h-5 w-5" />
      <EyeIcon v-else class="h-5 w-5" />
    </button>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, useAttrs } from 'vue';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const attrs = useAttrs();
const inputRef = ref(null);
const isPasswordVisible = ref(false);
let lastSelection = {
  start: null,
  end: null,
  direction: 'none',
};

const isPasswordField = computed(
  () => String(attrs.type ?? '').toLowerCase() === 'password',
);

const inputType = computed(() => {
  if (isPasswordField.value && isPasswordVisible.value) {
    return 'text';
  }

  return attrs.type ?? 'text';
});

const inputAttrs = computed(() => {
  const { type, ...rest } = attrs;

  return rest;
});

const inputClasses = computed(() => [
  'w-full bg-gray-800 border focus:ring-0 text-gray-200 rounded-lg px-3 py-2 text-sm placeholder-gray-500 transition-colors',
  isPasswordField.value ? 'pr-11' : '',
  props.error
    ? 'border-red-500 focus:border-red-500'
    : 'border-gray-700 focus:border-blue-500',
]);

const passwordToggleLabel = computed(() =>
  isPasswordVisible.value ? 'Ocultar senha' : 'Mostrar senha',
);

async function togglePasswordVisibility() {
  captureSelection();

  isPasswordVisible.value = !isPasswordVisible.value;

  await nextTick();

  requestAnimationFrame(restoreSelection);
}

function captureSelection() {
  const input = inputRef.value;

  if (!input) {
    return;
  }

  lastSelection = {
    start: input.selectionStart,
    end: input.selectionEnd,
    direction: input.selectionDirection ?? 'none',
  };
}

function restoreSelection() {
  const input = inputRef.value;

  if (!input) {
    return;
  }

  const cursorStart = lastSelection.start ?? input.value.length;
  const cursorEnd = lastSelection.end ?? input.value.length;

  input.focus();
  input.setSelectionRange(cursorStart, cursorEnd, lastSelection.direction);
  input.scrollLeft = input.scrollWidth;
  captureSelection();
}

function handleInput(event) {
  emit('update:modelValue', event.target.value);
  captureSelection();
}

function focus() {
  inputRef.value?.focus();
}

defineExpose({ focus, el: inputRef });
</script>
