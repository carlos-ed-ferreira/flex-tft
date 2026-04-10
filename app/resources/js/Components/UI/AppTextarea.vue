<template>
  <textarea
    ref="textareaRef"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    v-bind="$attrs"
    class="w-full bg-gray-900 border border-gray-800 focus:border-gray-700 focus:ring-0 text-sm text-gray-300 placeholder-gray-600 rounded-lg px-4 py-3 resize-none overflow-hidden"
  />
</template>

<script setup>
import { ref } from "vue";

defineOptions({ inheritAttrs: false });

defineProps({
  modelValue: { type: [String, Number], default: "" },
});

defineEmits(["update:modelValue"]);

const textareaRef = ref(null);

function focus() {
  textareaRef.value?.focus();
}

defineExpose({ focus, el: textareaRef });
</script>
