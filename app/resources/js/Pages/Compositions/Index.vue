<template>
    <AppLayout>
        <template #header-actions>
            <Link
                :href="route('compositions.create')"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition"
            >
                + Nova Composição
            </Link>
        </template>

        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Empty state -->
            <div v-if="compositions.length === 0" class="text-center py-20">
                <div class="w-20 h-20 mx-auto mb-6 bg-gray-800 rounded-2xl flex items-center justify-center">
                    <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-300 mb-2">Nenhuma composição ainda</h2>
                <p class="text-gray-500 mb-6">Crie sua primeira composição para começar a planejar.</p>
                <Link
                    :href="route('compositions.create')"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition"
                >
                    + Nova Composição
                </Link>
            </div>

            <!-- Compositions grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="comp in compositions"
                    :key="comp.id"
                    class="bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-gray-700 transition group"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white">{{ comp.name }}</h3>
                        </div>
                        <button
                            @click="confirmDelete(comp)"
                            class="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-800 rounded-lg transition opacity-0 group-hover:opacity-100"
                            title="Excluir"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Traits/Synergies -->
                    <div v-if="comp.traits.length > 0" class="mb-3">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <div
                                v-for="trait in comp.traits"
                                :key="trait.id"
                                class="flex items-center gap-1 px-2 py-0.5 rounded-lg"
                                :class="getTraitBgClass(trait.style)"
                            >
                                <div class="w-4 h-4 flex-shrink-0">
                                    <img
                                        v-if="trait.icon"
                                        :src="trait.icon"
                                        :alt="trait.name"
                                        class="w-full h-full object-contain"
                                        loading="lazy"
                                    />
                                </div>
                                <span class="text-xs font-medium" :class="getTraitTextClass(trait.style)">
                                    {{ trait.name }} ({{ trait.count }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Champions with 3 items -->
                    <div v-if="comp.champions.length > 0" class="mb-3">
                        <div class="flex items-center gap-3 flex-wrap">
                            <div
                                v-for="champion in comp.champions"
                                :key="champion.id"
                                class="flex flex-col items-center gap-1"
                            >
                                <div
                                    class="relative w-12 h-12 rounded-lg overflow-hidden border-2"
                                    :class="`cost-${getChampionCost(champion.id)}`"
                                    :style="{ borderColor: 'var(--cost-color)' }"
                                    :title="getChampionName(champion.id)"
                                >
                                    <img
                                        :src="getChampionIcon(champion.id)"
                                        :alt="getChampionName(champion.id)"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                                <div class="flex gap-0.5">
                                    <div
                                        v-for="(itemId, idx) in champion.items"
                                        :key="idx"
                                        class="w-5 h-5 rounded-sm overflow-hidden bg-gray-800"
                                        :title="getItemName(itemId)"
                                    >
                                        <img
                                            :src="getItemIcon(itemId)"
                                            :alt="getItemName(itemId)"
                                            class="w-full h-full object-cover"
                                            loading="lazy"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Click to open -->
                    <Link
                        :href="route('compositions.edit', comp.id)"
                        class="mt-3 block w-full text-center py-2 text-sm text-gray-400 hover:text-white bg-gray-800/50 hover:bg-gray-800 rounded-lg transition"
                    >
                        Abrir Editor
                    </Link>
                </div>
            </div>
        </div>

        <!-- Delete confirmation modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showDeleteModal = false">
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 max-w-sm w-full">
                    <h3 class="text-lg font-semibold text-white mb-2">Excluir composição?</h3>
                    <p class="text-gray-400 text-sm mb-6">
                        Tem certeza que deseja excluir "<strong class="text-gray-200">{{ deleteTarget?.name }}</strong>"? Essa ação não pode ser desfeita.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-lg transition"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="executeDelete"
                            class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg transition"
                        >
                            Excluir
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    compositions: {
        type: Array,
        default: () => [],
    },
    tftData: {
        type: Object,
        default: () => ({ champions: [], traits: [], items: [] }),
    },
});

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const championsMap = computed(() => {
    const map = {};
    props.tftData.champions.forEach(champ => {
        map[champ.id] = champ;
    });
    return map;
});

function getChampionIcon(championId) {
    return championsMap.value[championId]?.icon || '';
}

function getChampionName(championId) {
    return championsMap.value[championId]?.name || championId;
}

function getChampionCost(championId) {
    return championsMap.value[championId]?.cost || 1;
}

const itemsMap = computed(() => {
    const map = {};
    props.tftData.items.forEach(item => {
        map[item.id] = item;
    });
    return map;
});

function getItemIcon(itemId) {
    return itemsMap.value[itemId]?.icon || '';
}

function getItemName(itemId) {
    return itemsMap.value[itemId]?.name || itemId;
}

function getTraitBgClass(style) {
    const classes = {
        1: 'bg-amber-900/40',
        2: 'bg-gray-400/20',
        3: 'bg-yellow-600/30',
        4: 'bg-cyan-600/30',
    };
    return classes[style] || 'bg-gray-800/50';
}

function getTraitTextClass(style) {
    const classes = {
        1: 'text-amber-300',
        2: 'text-gray-300',
        3: 'text-yellow-300',
        4: 'text-cyan-300',
    };
    return classes[style] || 'text-gray-400';
}

function confirmDelete(comp) {
    deleteTarget.value = comp;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    router.delete(route('compositions.destroy', deleteTarget.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>
