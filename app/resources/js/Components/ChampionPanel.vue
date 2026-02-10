<template>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-3">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">Campeões</h3>

        <!-- Search -->
        <input
            v-model="search"
            type="text"
            placeholder="Buscar campeão..."
            class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-xs text-gray-200 rounded-lg px-3 py-1.5 mb-3"
        />

        <!-- Cost filters -->
        <div class="flex gap-1 mb-3">
            <button
                v-for="cost in [1, 2, 3, 4, 5]"
                :key="cost"
                @click="toggleCostFilter(cost)"
                class="flex-1 py-1 text-xs font-medium rounded transition-all"
                :class="[
                    activeCosts.has(cost)
                        ? costClasses[cost].active
                        : 'bg-gray-800 text-gray-500 hover:bg-gray-700',
                ]"
            >
                ${{ cost }}
            </button>
        </div>

        <!-- Champions grid -->
        <div class="grid grid-cols-5 gap-1.5 max-h-[400px] overflow-y-auto pr-1">
            <div
                v-for="champion in filteredChampions"
                :key="champion.id"
                class="champion-grid-item"
                :class="`cost-${champion.cost}`"
                draggable="true"
                @dragstart="onDragStart($event, champion)"
                @click="$emit('select', champion)"
                :title="`${champion.name} ($${champion.cost}) - ${champion.traits.map(t => t.name).join(', ')}`"
            >
                <div class="w-full aspect-square bg-gray-800 rounded overflow-hidden" :style="{ borderBottom: `3px solid var(--cost-color)` }">
                    <img
                        v-if="champion.icon"
                        :src="champion.icon"
                        :alt="champion.name"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                </div>
                <div class="text-[9px] text-gray-400 text-center truncate mt-0.5 px-0.5">
                    {{ champion.name }}
                </div>
            </div>
        </div>

        <div v-if="filteredChampions.length === 0" class="text-center py-4 text-xs text-gray-600">
            Nenhum campeão encontrado
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    champions: { type: Array, default: () => [] },
});

const emit = defineEmits(['select']);

const search = ref('');
const activeCosts = ref(new Set());

const costClasses = {
    1: { active: 'bg-gray-600 text-white' },
    2: { active: 'bg-green-700 text-white' },
    3: { active: 'bg-blue-700 text-white' },
    4: { active: 'bg-purple-700 text-white' },
    5: { active: 'bg-yellow-600 text-white' },
};

function toggleCostFilter(cost) {
    const newSet = new Set(activeCosts.value);
    if (newSet.has(cost)) {
        newSet.delete(cost);
    } else {
        newSet.add(cost);
    }
    activeCosts.value = newSet;
}

const filteredChampions = computed(() => {
    return props.champions.filter(champ => {
        // Cost filter
        if (activeCosts.value.size > 0 && !activeCosts.value.has(champ.cost)) {
            return false;
        }
        // Search filter
        if (search.value) {
            const s = search.value.toLowerCase();
            const nameMatch = champ.name.toLowerCase().includes(s);
            const traitMatch = champ.traits.some(t => t.name.toLowerCase().includes(s));
            if (!nameMatch && !traitMatch) return false;
        }
        return true;
    });
});

function onDragStart(event, champion) {
    event.dataTransfer.effectAllowed = 'copy';
    event.dataTransfer.setData('application/tft-champion', JSON.stringify(champion));
}
</script>
