<template>
    <AppLayout>
        <template #header-actions>
            <div class="flex items-center gap-2">
                <button
                    @click="router.visit(route('compositions.index'))"
                    class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium rounded-lg transition flex items-center gap-2"
                >
                    Voltar
                </button>
                <button
                    @click="save"
                    :disabled="saving"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition flex items-center gap-2"
                >
                    <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ saving ? 'Salvando...' : 'Salvar' }}
                </button>
            </div>
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

            <!-- Main builder area -->
            <div class="flex gap-4 mt-4">
                <!-- Synergy panel (left) -->
                <div class="w-56 flex-shrink-0 hidden xl:block">
                    <SynergyPanel :activeTraits="activeTraits" />
                </div>

                <!-- Center: Level tabs + Board + Champions below -->
                <div class="flex-1 flex flex-col">
                    <!-- Level tabs + Copy/Paste buttons -->
                    <div class="mb-4 flex justify-center items-center gap-3">
                        <LevelTabs
                            :levels="LEVELS"
                            :activeLevel="activeLevel"
                            :levelChampionCounts="levelChampionCounts"
                            @select="switchLevel"
                        />
                        <div class="flex gap-2">
                            <button
                                @click="copyLevel"
                                class="px-3 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-medium rounded-lg transition flex items-center gap-1.5"
                                title="Copiar composição atual"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copiar
                            </button>
                            <button
                                @click="pasteLevel"
                                :disabled="!clipboard"
                                class="px-3 py-2 bg-gray-800 hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed text-gray-300 text-xs font-medium rounded-lg transition flex items-center gap-1.5"
                                title="Colar composição copiada"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Colar
                            </button>
                            <button
                                @click="clearLevel"
                                class="px-3 py-2 bg-red-900/40 hover:bg-red-900/60 text-red-300 text-xs font-medium rounded-lg transition flex items-center gap-1.5"
                                title="Limpar composição atual"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Limpar
                            </button>
                        </div>
                    </div>

                    <!-- Board -->
                    <div 
                        class="flex flex-col items-center mb-4"
                        @dragover.prevent="onBoardAreaDragOver"
                        @drop="onBoardAreaDrop"
                    >
                        <HexBoard
                            ref="hexBoard"
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
                    </div>

                    <!-- Mobile synergies -->
                    <div class="xl:hidden mb-4">
                        <SynergyPanel :activeTraits="activeTraits" :horizontal="true" />
                    </div>

                    <!-- Champions below board -->
                    <ChampionPanel
                        :champions="tftData.champions"
                        @select="onSelectChampion"
                    />

                    <!-- Notes -->
                    <div class="mt-4">
                        <textarea
                            ref="notesTextarea"
                            v-model="form.notes"
                            @input="autoResizeTextarea"
                            placeholder="Anotações sobre esta composição..."
                            rows="5"
                            spellcheck="false"
                            class="w-full bg-gray-900 border border-gray-800 focus:border-gray-700 focus:ring-0 text-sm text-gray-300 placeholder-gray-600 rounded-lg px-4 py-3 resize-none overflow-hidden"
                        />
                    </div>
                </div>

                <!-- Right panel: Items -->
                <div class="w-72 flex-shrink-0">
                    <ItemPanel
                        :items="tftData.items"
                        @select="onSelectItem"
                    />
                </div>
            </div>
        </div>

        <!-- Champion selector modal -->
        <Teleport to="body">
            <div v-if="championSelectorOpen" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="closeChampionSelector">
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">Selecionar Campeão</h3>
                        <button @click="closeChampionSelector" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <input
                        v-model="championSelectorSearch"
                        type="text"
                        placeholder="Buscar campeão..."
                        class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-sm text-gray-200 rounded-lg px-3 py-2 mb-4"
                    />
                    <div class="grid grid-cols-10 gap-2">
                        <div
                            v-for="champion in filteredModalChampions"
                            :key="champion.id"
                            @click="selectChampionFromModal(champion)"
                            class="champion-grid-item cursor-pointer"
                            :class="`cost-${champion.cost}`"
                            :title="`${champion.name} ($${champion.cost})`"
                        >
                            <div class="w-full aspect-square bg-gray-800 rounded overflow-hidden" :style="{ borderBottom: `2px solid var(--cost-color)` }">
                                <img
                                    v-if="champion.icon"
                                    :src="champion.icon"
                                    :alt="champion.name"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Item selector modal -->
        <Teleport to="body">
            <div v-if="itemSelectorOpen" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="closeItemSelector">
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
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
const activeLevel = ref(null);
const saving = ref(false);

// Store board states per level
const levelStates = ref({});
const tftDataRef = computed(() => props.tftData);

// Clipboard for copy/paste between levels
const clipboard = ref(null);

// Textarea auto-resize
const notesTextarea = ref(null);

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

// Champion selector modal state
const championSelectorOpen = ref(false);
const championSelectorSearch = ref('');
const championSelectorTarget = ref(null); // { row, col }

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
    
    // Find highest green level (where championCount === level)
    let highestGreenLevel = LEVELS[0];
    for (let i = LEVELS.length - 1; i >= 0; i--) {
        const lvl = LEVELS[i];
        const state = levelStates.value[lvl] || {};
        const count = Object.values(state).filter(cell => cell?.championId).length;
        if (count === lvl) {
            highestGreenLevel = lvl;
            break;
        }
    }
    
    activeLevel.value = highestGreenLevel;
    loadState(levelStates.value[activeLevel.value] || {});
    
    // Initialize textarea height
    if (notesTextarea.value) {
        autoResizeTextarea();
    }
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

// Copy/Paste between levels
function copyLevel() {
    clipboard.value = JSON.parse(JSON.stringify(exportState()));
}

function pasteLevel() {
    if (!clipboard.value) return;
    loadState(JSON.parse(JSON.stringify(clipboard.value)));
}

function clearLevel() {
    loadState({});
}

// Textarea auto-resize
function autoResizeTextarea() {
    const textarea = notesTextarea.value;
    if (!textarea) return;
    
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
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
    } else {
        // Open champion selector modal
        openChampionSelector({ row, col });
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

function openChampionSelector({ row, col }) {
    championSelectorTarget.value = { row, col };
    championSelectorSearch.value = '';
    championSelectorOpen.value = true;
}

// Drag champion outside board to remove
const hexBoard = ref(null);

function onBoardAreaDragOver(event) {
    // Allow drop outside board
    event.preventDefault();
}

function onBoardAreaDrop(event) {
    event.preventDefault();
    
    // Check if it's a champion being dragged from board
    const cellData = event.dataTransfer.getData('application/tft-cell');
    if (!cellData) return;
    
    const { fromRow, fromCol, type } = JSON.parse(cellData);
    if (type !== 'board-champion') return;
    
    // Check if drop happened outside the board element
    if (hexBoard.value && hexBoard.value.$el) {
        const boardRect = hexBoard.value.$el.getBoundingClientRect();
        const isOutsideBoard = (
            event.clientX < boardRect.left ||
            event.clientX > boardRect.right ||
            event.clientY < boardRect.top ||
            event.clientY > boardRect.bottom
        );
        
        if (isOutsideBoard) {
            removeChampion(fromRow, fromCol);
        }
    }
}

function closeChampionSelector() {
    championSelectorOpen.value = false;
    championSelectorTarget.value = null;
}

const filteredModalChampions = computed(() => {
    if (!props.tftData?.champions) return [];
    const search = championSelectorSearch.value.toLowerCase();
    return props.tftData.champions.filter(champion => {
        if (search) {
            const nameMatch = champion.name.toLowerCase().includes(search);
            const traitMatch = champion.traits?.some(t => t.name?.toLowerCase().includes(search));
            if (!nameMatch && !traitMatch) return false;
        }
        return true;
    });
});

function selectChampionFromModal(champion) {
    if (championSelectorTarget.value) {
        placeChampion(championSelectorTarget.value.row, championSelectorTarget.value.col, champion.id);
    }
    closeChampionSelector();
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
