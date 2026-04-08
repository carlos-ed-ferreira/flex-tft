<template>
    <AppLayout>
        <template #header-actions>
            <div class="flex items-center gap-2">
                <Link
                    :href="route('simulator.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition"
                >
                    <AcademicCapIcon class="w-4 h-4" />
                    Simular Abertura
                </Link>
                <Link
                    :href="route('compositions.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition"
                >
                    <PlusIcon class="w-4 h-4" />
                    Nova Composição
                </Link>
            </div>
        </template>

        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Search bar -->
            <div v-if="compositions.length > 0" class="mb-4">
                <AppInput
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar composições..."
                    class="w-full md:w-[calc(50%-0.5rem)] xl:w-[calc(33.333%-0.667rem)] bg-gray-900 border-gray-800 focus:ring-1 focus:ring-blue-500 rounded-xl px-4 py-2.5"
                />
            </div>

            <!-- Empty state -->
            <AppEmptyState
                v-if="filteredCompositions.length === 0 && searchQuery"
                :icon="MagnifyingGlassIcon"
                title="Nenhuma composição encontrada"
                description="Tente buscar com outros termos."
            />

            <AppEmptyState
                v-else-if="compositions.length === 0"
                :icon="ArchiveBoxIcon"
                title="Nenhuma composição ainda"
                description="Crie sua primeira composição para começar a planejar."
            >
                <template #action>
                    <Link
                        :href="route('compositions.create')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Nova Composição
                    </Link>
                </template>
            </AppEmptyState>

            <!-- Compositions grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="comp in filteredCompositions"
                    :key="comp.id"
                    class="bg-gray-900 border border-gray-800 rounded-xl p-5 hover:border-gray-700 transition group"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white">{{ comp.name }}</h3>
                        </div>
                        <div class="flex gap-1">
                            <button
                                @click="duplicateComposition(comp)"
                                class="p-2 text-gray-400 hover:text-blue-400 hover:bg-gray-800 rounded-lg transition opacity-0 group-hover:opacity-100"
                                title="Duplicar"
                            >
                                <DocumentDuplicateIcon class="w-4 h-4" />
                            </button>
                            <button
                                @click="confirmDelete(comp)"
                                class="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-800 rounded-lg transition opacity-0 group-hover:opacity-100"
                                title="Excluir"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
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

                    <hr v-if="comp.dispositions && comp.dispositions.length > 0" class="border-gray-600 mb-3">

                    <!-- Dispositions -->
                    <div v-if="comp.dispositions && comp.dispositions.length > 0" class="mb-3">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <div
                                v-for="(disp, dIdx) in comp.dispositions"
                                :key="dIdx"
                                class="flex items-center gap-1 px-2 py-0.5 rounded-lg"
                                :class="dispBgClass(disp.type)"
                            >
                                <template v-if="disp.type === 'champion'">
                                    <div class="flex items-center gap-0.5">
                                        <div
                                            v-for="champId in (disp.champion_ids || []).slice(0, 4)"
                                            :key="champId"
                                            class="w-4 h-4 rounded-sm overflow-hidden"
                                            :title="getChampionName(champId)"
                                        >
                                            <img :src="getChampionIcon(champId)" :alt="getChampionName(champId)" class="w-full h-full object-cover" />
                                        </div>
                                        <span v-if="(disp.champion_ids || []).length > 4" class="text-[9px] text-gray-500">+{{ (disp.champion_ids || []).length - 4 }}</span>
                                    </div>
                                    <span class="text-xs font-medium text-purple-300">
                                        <template v-if="disp.star_level">{{ '★'.repeat(disp.star_level) }}</template>
                                    </span>
                                </template>
                                <template v-else-if="disp.type === 'trait'">
                                    <img
                                        v-if="getTraitIcon(disp.trait_id)"
                                        :src="getTraitIcon(disp.trait_id)"
                                        :alt="getTraitName(disp.trait_id)"
                                        class="w-4 h-4 object-contain"
                                    />
                                    <span class="text-xs font-medium text-amber-300">
                                        {{ disp.trait_count || 1 }}x {{ getTraitName(disp.trait_id) }}
                                    </span>
                                </template>
                                <template v-else-if="disp.type === 'item'">
                                    <div class="flex items-center gap-0.5">
                                        <div
                                            v-for="itemId in (disp.item_ids || []).slice(0, 3)"
                                            :key="itemId"
                                            class="w-4 h-4 rounded-sm overflow-hidden"
                                            :title="getItemName(itemId)"
                                        >
                                            <img :src="getItemIcon(itemId)" :alt="getItemName(itemId)" class="w-full h-full object-cover" />
                                        </div>
                                        <span v-if="(disp.item_ids || []).length > 3" class="text-[9px] text-gray-500">+{{ (disp.item_ids || []).length - 3 }}</span>
                                    </div>
                                </template>
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
        <AppModal :show="showDeleteModal" title="Excluir composição?" max-width="sm" @close="showDeleteModal = false">
            <p class="text-gray-400 text-sm mb-6">
                Tem certeza que deseja excluir "<strong class="text-gray-200">{{ deleteTarget?.name }}</strong>"? Essa ação não pode ser desfeita.
            </p>
            <div class="flex justify-end gap-3">
                <AppButton variant="secondary" @click="showDeleteModal = false">Cancelar</AppButton>
                <AppButton variant="danger" class="bg-red-600 hover:bg-red-700 text-white" @click="executeDelete">Excluir</AppButton>
            </div>
        </AppModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon, AcademicCapIcon, DocumentDuplicateIcon, TrashIcon, MagnifyingGlassIcon, ArchiveBoxIcon } from '@heroicons/vue/24/outline';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppEmptyState from '@/Components/UI/AppEmptyState.vue';

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
const searchQuery = ref('');

const filteredCompositions = computed(() => {
    if (!searchQuery.value) return props.compositions;
    
    const query = searchQuery.value.toLowerCase();
    return props.compositions.filter(comp => {
        return comp.name.toLowerCase().includes(query);
    });
});

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

const traitsMap = computed(() => {
    const map = {};
    (props.tftData.traits || []).forEach(t => { map[t.id] = t; });
    return map;
});

function getTraitIcon(traitId) {
    return traitsMap.value[traitId]?.icon || '';
}

function getTraitName(traitId) {
    return traitsMap.value[traitId]?.name || traitId;
}

function dispBgClass(type) {
    return {
        champion: 'bg-purple-900/30',
        trait: 'bg-amber-900/30',
        item: 'bg-blue-900/30',
    }[type] || 'bg-gray-800/50';
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

function duplicateComposition(comp) {
    router.post(route('compositions.duplicate', comp.id));
}
</script>
