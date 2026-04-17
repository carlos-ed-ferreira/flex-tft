<template>
  <Link
    v-if="href"
    :href="href"
    v-bind="$attrs"
    :class="[baseClasses, variantClasses, sizeClasses, pillClasses]"
    :aria-disabled="disabled || loading"
  >
    <ArrowPathIcon v-if="loading" class="animate-spin" :class="iconSizeClass" />
    <slot />
  </Link>

  <button
    v-else
    :type="type"
    :disabled="disabled || loading"
    :class="[baseClasses, variantClasses, sizeClasses, pillClasses]"
    v-bind="$attrs"
  >
    <ArrowPathIcon v-if="loading" class="animate-spin" :class="iconSizeClass" />
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
  pill: { type: Boolean, default: false },
  href: { type: String, default: null },
});

const baseClasses =
  'inline-flex items-center gap-2 font-medium transition disabled:opacity-50 disabled:cursor-not-allowed';

const variantClasses = computed(
  () =>
    ({
      primary: 'bg-blue-600 hover:bg-blue-700 text-white',
      secondary: 'bg-gray-900 hover:bg-gray-700 text-white',
      danger: 'bg-red-900/40 hover:bg-red-900/60 text-red-300',
      ghost: 'text-gray-400 hover:text-white hover:bg-gray-800',
    })[props.variant] || '',
);

const sizeClasses = computed(
  () =>
    ({
      xs: 'text-[10px]',
      sm: 'text-xs',
      md: 'text-sm',
    })[props.size] || '',
);

const pillClasses = computed(() => {
  if (!props.pill) return 'rounded-lg px-4 py-2';
  const base = 'rounded-full px-3 py-1.5 border';
  const borderByVariant = {
    primary: 'border-blue-500/80',
    secondary: 'border-gray-400',
    danger: 'border-red-700/60',
    ghost: 'border-transparent',
  };
  return `${base} ${borderByVariant[props.variant] || 'border-gray-600'}`;
});

const iconSizeClass = computed(
  () =>
    ({
      xs: 'w-3 h-3',
      sm: 'w-4 h-4',
      md: 'w-4 h-4',
    })[props.size] || 'w-4 h-4',
);
</script>
