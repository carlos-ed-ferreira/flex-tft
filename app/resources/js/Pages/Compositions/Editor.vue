<template>
    <AppLayout>
        <template #header-actions>
            <button
                @click="save"
                :disabled="saving"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition flex items-center gap-2"
            >
                <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ saving ? 'Salvando...' : 'Salvar' }}
            </button>
        </template>

        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <!-- Composition name -->
            <div class="mb-4">
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="Nome da composição..."
                    class="w-full bg-transparent border-0 border-b border-gray-700 focus:border-blue-500 focus:ring-0 text-2xl font-bold text-white placeholder-gray-600 px-0 py-2"
                />
            </div>

            <!-- Level tabs -->
            <LevelTabs
                :levels="LEVELS"
                :activeLevel="activeLevel"
                :levelChampionCounts="levelChampionCounts"
                @select="switchLevel"
            />

            <!-- Main builder area -->
            <div class="flex gap-4 mt-4">
                <!-- Synergy panel (left) -->
                <div class="w-56 flex-shrink-0 hidden lg:block">
                    <SynergyPanel :activeTraits="activeTraits" />
                </div>

                <!-- Board (center) -->
                <div class="flex-1 flex flex-col items-center">
                    <div class="text-sm text-gray-500 mb-2">
                        Level {{ activeLevel }} — {{ championCount }} / {{ activeLevel }} campeões
                    </div>
                    <HexBoard
                        :boardState="boardState"
                        :tftData="tftData"
                        :rows="ROWS"
                        :cols="COLS"
                        @place-champion="onPlaceChampion"
                        @remove-champion="onRemoveChampion"
                        @move-champion="onMoveChampion"
                        @add-item="onAddItem"
                        @remove-item="onRemoveItem"
                        @open-item-selector="openItemSelector"
                    />

                    <!-- Mobile synergies -->
                    <div class="lg:hidden mt-4 w-full">
                        <SynergyPanel :activeTraits="activeTraits" :horizontal="true" />
                    </div>
                </div>

                <!-- Right panel: Champions + Items -->
                <div class="w-72 flex-shrink-0 flex flex-col gap-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                    <ChampionPanel
                        :champions="tftData.champions"
                        @select="onSelectChampion"
                    />
                    <ItemPanel
                        :items="tftData.items"
                        @select="onSelectItem"
                    />
                </div>
            </div>

            <!-- Notes -->
            <div class="mt-6">
                <textarea
                    v-model="form.notes"
                    placeholder="Anotações sobre esta composição..."
                    rows="2"
                    class="w-full bg-gray-900 border border-gray-800 focus:border-gray-700 focus:ring-0 text-sm text-gray-300 placeholder-gray-600 rounded-lg px-4 py-3 resize-y"
                />
            </div>
        </div>

        <!-- Item selector modal -->
        <Teleport to="body">
            <div v-if="itemSelectorOpen" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="closeItemSelector">
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">Selecionar Item</h3>
                        <button @click="closeItemSelector" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <input
                        v-model="itemSelectorSearch"
                        type="text"
                        placeholder="Buscar item..."
                        class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-sm text-gray-200 rounded-lg px-3 py-2 mb-4"
                    />
                    <div class="grid grid-cols-8 gap-2">
                        <div
                            v-for="item in filteredModalItems"
                            :key="item.id"
                            @click="selectItemFromModal(item)"
                            class="item-grid-item w-10 h-10 cursor-pointer"
                            :title="item.name"
                        >
                            <img :src="item.icon" :alt="item.name" class="w-full h-full object-cover rounded" loading="lazy" />
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LevelTabs from '@/Components/LevelTabs.vue';
import HexBoard from '@/Components/HexBoard.vue';
import SynergyPanel from '@/Components/SynergyPanel.vue';
import ChampionPanel from '@/Components/ChampionPanel.vue';
import ItemPanel from '@/Components/ItemPanel.vue';
import { useBoardState } from '@/composables/useBoardState';

const props = defineProps({
    composition: Object, // null for create
    levels: Array,
    tftData: Object,
});

const LEVELS = [3, 4, 5, 6, 7, 8, 9, 10];
const activeLevel = ref(LEVELS[0]);
const saving = ref(false);

// Store board states per level
const levelStates = ref({});
const tftDataRef = computed(() => props.tftData);

const {
    ROWS, COLS, boardState, loadState, exportState,
    placeChampion, removeChampion, moveChampion, addItem, removeItem,
    championCount, activeTraits,
} = useBoardState(tftDataRef);

// Form data
const form = ref({
    name: props.composition?.name || '',
    notes: props.composition?.notes || '',
});

// Item selector modal state
const itemSelectorOpen = ref(false);
const itemSelectorSearch = ref('');
const itemSelectorTarget = ref(null); // { row, col }

// Selected champion for placement mode
const selectedChampion = ref(null);

// Selected item for placement mode
const selectedItem = ref(null);

// Initialize level states from props
onMounted(() => {
    for (const level of props.levels) {
        levelStates.value[level.level] = level.board_state || {};
    }
    loadState(levelStates.value[activeLevel.value] || {});
});

// Champion counts per level for tabs
const levelChampionCounts = computed(() => {
    const counts = {};
    for (const lvl of LEVELS) {
        const state = lvl === activeLevel.value ? boardState.value : (levelStates.value[lvl] || {});
        counts[lvl] = Object.values(state).filter(cell => cell?.championId).length;
    }
    return counts;
});

function switchLevel(newLevel) {
    // Save current level state
    levelStates.value[activeLevel.value] = exportState();
    // Switch to new level
    activeLevel.value = newLevel;
    loadState(levelStates.value[newLevel] || {});
}

// Champion placement from panel
function onSelectChampion(champion) {
    selectedChampion.value = champion;
    selectedItem.value = null;
}

// Item placement from panel
function onSelectItem(item) {
    selectedItem.value = item;
    selectedChampion.value = null;
}

// Board events
function onPlaceChampion({ row, col, championId }) {
    if (championId) {
        placeChampion(row, col, championId);
    } else if (selectedChampion.value) {
        placeChampion(row, col, selectedChampion.value.id);
    }
}

function onRemoveChampion({ row, col }) {
    removeChampion(row, col);
}

function onMoveChampion({ fromRow, fromCol, toRow, toCol }) {
    moveChampion(fromRow, fromCol, toRow, toCol);
}

function onAddItem({ row, col, itemId }) {
    addItem(row, col, itemId);
}

function onRemoveItem({ row, col, itemIndex }) {
    removeItem(row, col, itemIndex);
}

function openItemSelector({ row, col }) {
    itemSelectorTarget.value = { row, col };
    itemSelectorSearch.value = '';
    itemSelectorOpen.value = true;
}

function closeItemSelector() {
    itemSelectorOpen.value = false;
    itemSelectorTarget.value = null;
}

const filteredModalItems = computed(() => {
    if (!props.tftData?.items) return [];
    const search = itemSelectorSearch.value.toLowerCase();
    return props.tftData.items.filter(item => {
        if (search && !item.name.toLowerCase().includes(search)) return false;
        return true;
    });
});

function selectItemFromModal(item) {
    if (itemSelectorTarget.value) {
        addItem(itemSelectorTarget.value.row, itemSelectorTarget.value.col, item.id);
    }
    closeItemSelector();
}

// Save composition
function save() {
    saving.value = true;

    // Save current level state
    levelStates.value[activeLevel.value] = exportState();

    const data = {
        name: form.value.name || 'Composição sem nome',
        notes: form.value.notes || null,
        levels: LEVELS.map(level => {
            const boardState = levelStates.value[level];
            // Convert to plain object to avoid Inertia converting {} to []
            const plainBoardState = boardState ? JSON.parse(JSON.stringify(boardState)) : {};
            return {
                level,
                board_state: plainBoardState,
            };
        }),
    };

    if (props.composition?.id) {
        router.put(route('compositions.update', props.composition.id), data, {
            onFinish: () => { saving.value = false; },
        });
    } else {
        router.post(route('compositions.store'), data, {
            onFinish: () => { saving.value = false; },
        });
    }
}
</script>
