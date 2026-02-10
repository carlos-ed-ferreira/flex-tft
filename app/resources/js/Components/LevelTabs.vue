<template>
    <div class="flex items-center gap-1 bg-gray-900 rounded-xl p-1 border border-gray-800">
        <button
            v-for="level in levels"
            :key="level"
            @click="$emit('select', level)"
            class="relative px-4 py-2 text-sm font-medium rounded-lg transition-all"
            :class="[
                activeLevel === level
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
                    : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800',
            ]"
        >
            Lv{{ level }}
            <span
                v-if="levelChampionCounts[level] > 0 && activeLevel !== level"
                class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full text-[10px] flex items-center justify-center text-white"
            >
                {{ levelChampionCounts[level] }}
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
