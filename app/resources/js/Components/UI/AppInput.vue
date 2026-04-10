<template>
  <input
    ref="inputRef"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    v-bind="$attrs"
    class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-gray-200 rounded-lg px-3 py-2 text-sm placeholder-gray-500"
  />
</template>

<script setup>
import { ref } from "vue";

defineOptions({ inheritAttrs: false });

defineProps({
  modelValue: { type: [String, Number], default: "" },
});

defineEmits(["update:modelValue"]);

const inputRef = ref(null);

function focus() {
  inputRef.value?.focus();
}

defineExpose({ focus, el: inputRef });
</script>
