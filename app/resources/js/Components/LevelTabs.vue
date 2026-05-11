<template>
  <div
    class="flex items-center gap-1 rounded-lg border border-gray-800 bg-gray-900 p-0.5"
  >
    <button
      v-for="level in levels"
      :key="level"
      @click="$emit('select', level)"
      class="relative rounded-md px-4 py-1.5 text-sm font-medium transition-all"
      :class="[
        activeLevel === level
          ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
          : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800',
      ]"
    >
      <span
        class="text-xs font-semibold"
        :class="{
          'text-red-400': (levelChampionCounts[level] || 0) > level,
          'text-green-400': (levelChampionCounts[level] || 0) === level,
          'opacity-70': (levelChampionCounts[level] || 0) < level,
        }"
      >
        {{ levelChampionCounts[level] || 0 }}/{{ level }}
      </span>
    </button>
  </div>
</template>

<script setup>
defineProps({
  levels: { type: Array, required: true },
  activeLevel: { type: Number, required: true },
  levelChampionCounts: { type: Object, default: () => ({}) },
});

defineEmits(['select']);
</script>
