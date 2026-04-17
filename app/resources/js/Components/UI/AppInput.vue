<template>
  <input
    ref="inputRef"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    v-bind="$attrs"
    :class="[
      'w-full bg-gray-800 border focus:ring-0 text-gray-200 rounded-lg px-3 py-2 text-sm placeholder-gray-500 transition-colors',
      error
        ? 'border-red-500 focus:border-red-500'
        : 'border-gray-700 focus:border-blue-500',
    ]"
  />
</template>

<script setup>
import { ref } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps({
  modelValue: { type: [String, Number], default: '' },
  error: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const inputRef = ref(null);

function focus() {
  inputRef.value?.focus();
}

defineExpose({ focus, el: inputRef });
</script>
