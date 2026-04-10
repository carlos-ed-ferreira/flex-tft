<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[baseClasses, variantClasses, sizeClasses]"
    v-bind="$attrs"
  >
    <ArrowPathIcon v-if="loading" class="animate-spin" :class="iconSizeClass" />
    <slot />
  </button>
</template>

<script setup>
import { computed } from "vue";
import { ArrowPathIcon } from "@heroicons/vue/24/outline";

defineOptions({ inheritAttrs: false });

const props = defineProps({
  variant: { type: String, default: "primary" },
  size: { type: String, default: "md" },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  type: { type: String, default: "button" },
});

const baseClasses =
  "inline-flex items-center gap-2 font-medium rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed";

const variantClasses = computed(
  () =>
    ({
      primary: "bg-blue-600 hover:bg-blue-700 text-white",
      secondary: "bg-gray-800 hover:bg-gray-700 text-gray-300",
      danger: "bg-red-900/40 hover:bg-red-900/60 text-red-300",
      ghost: "text-gray-400 hover:text-white hover:bg-gray-800",
    })[props.variant] || "",
);

const sizeClasses = computed(
  () =>
    ({
      xs: "px-2 py-1 text-[10px]",
      sm: "px-3 py-2 text-xs",
      md: "px-4 py-2 text-sm",
    })[props.size] || "",
);

const iconSizeClass = computed(
  () =>
    ({
      xs: "w-3 h-3",
      sm: "w-4 h-4",
      md: "w-4 h-4",
    })[props.size] || "w-4 h-4",
);
</script>
