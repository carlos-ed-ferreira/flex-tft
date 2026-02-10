<template>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-3">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">Itens</h3>

        <!-- Search -->
        <input
            v-model="search"
            type="text"
            placeholder="Buscar item..."
            class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-xs text-gray-200 rounded-lg px-3 py-1.5 mb-3"
        />

        <!-- Category filters -->
        <div class="flex gap-1 mb-3">
            <button
                v-for="cat in categories"
                :key="cat.key"
                @click="activeCategory = cat.key"
                class="flex-1 py-1 text-[10px] font-medium rounded transition-all"
                :class="[
                    activeCategory === cat.key
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-800 text-gray-500 hover:bg-gray-700',
                ]"
            >
                {{ cat.label }}
            </button>
        </div>

        <!-- Items grid -->
        <div class="grid grid-cols-6 gap-1.5 max-h-[300px] overflow-y-auto pr-1">
            <div
                v-for="item in filteredItems"
                :key="item.id"
                class="item-grid-item w-10 h-10"
                draggable="true"
                @dragstart="onDragStart($event, item)"
                @click="$emit('select', item)"
                :title="item.name"
            >
                <img
                    v-if="item.icon"
                    :src="item.icon"
                    :alt="item.name"
                    class="w-full h-full object-cover rounded"
                    loading="lazy"
                />
            </div>
        </div>

        <div v-if="filteredItems.length === 0" class="text-center py-4 text-xs text-gray-600">
            Nenhum item encontrado
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const emit = defineEmits(['select']);

const search = ref('');
const activeCategory = ref('all');

const categories = [
    { key: 'all', label: 'Todos' },
    { key: 'component', label: 'Base' },
    { key: 'combined', label: 'Combo' },
    { key: 'emblem', label: 'Emblem' },
    { key: 'artifact', label: 'Artif.' },
];

const filteredItems = computed(() => {
    return props.items.filter(item => {
        // Category filter
        if (activeCategory.value !== 'all' && item.category !== activeCategory.value) {
            return false;
        }
        // Search filter
        if (search.value && !item.name.toLowerCase().includes(search.value.toLowerCase())) {
            return false;
        }
        return true;
    });
});

function onDragStart(event, item) {
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData('application/tft-item', JSON.stringify(item));
}
</script>
