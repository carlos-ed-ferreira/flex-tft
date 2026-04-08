<template>
    <div class="text-center py-20">
        <div class="w-20 h-20 mx-auto mb-6 bg-gray-800 rounded-2xl flex items-center justify-center">
            <component :is="icon" class="w-10 h-10 text-gray-600" />
        </div>
        <h2 class="text-xl font-semibold text-gray-300 mb-2">{{ title }}</h2>
        <p v-if="description" class="text-gray-500 mb-6">{{ description }}</p>
        <slot name="action" />
    </div>
</template>

<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>
