<template>
  <AppLayout>
    <template #header-actions>
      <div class="flex items-center gap-2">
        <AppButton
          variant="secondary"
          @click="router.visit(route('compositions.my'))"
        >
          <ChevronLeftIcon class="w-4 h-4" />
          <span>Voltar</span>
        </AppButton>
        <AppButton
          variant="primary"
          :loading="saving"
          :disabled="saving"
          @click="save"
        >
          <ArchiveBoxIcon v-if="!saving" class="w-4 h-4" />
          <span>{{ saving ? 'Salvando...' : 'Salvar' }}</span>
        </AppButton>
      </div>
    </template>

    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <!-- Composition name -->
      <div class="mb-4">
        <AppInput
          v-model="form.name"
          type="text"
          placeholder="Nome da composição..."
          class="w-full bg-transparent border-0 border-b border-gray-700 focus:border-blue-500 text-2xl font-bold text-white placeholder-gray-600 px-0 py-2 rounded-none"
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
              <AppButton
                @click="copyLevel"
                variant="secondary"
                size="sm"
                title="Copiar composição atual"
              >
                <DocumentDuplicateIcon class="w-4 h-4" />
                <span>Copiar</span>
              </AppButton>
              <AppButton
                @click="pasteLevel"
                :disabled="!clipboard"
                variant="secondary"
                size="sm"
                title="Colar composição copiada"
              >
                <ClipboardDocumentIcon class="w-4 h-4" />
                <span>Colar</span>
              </AppButton>
              <AppButton
                @click="clearLevel"
                variant="danger"
                size="sm"
                title="Limpar composição atual"
              >
                <TrashIcon class="w-4 h-4" />
                <span>Limpar</span>
              </AppButton>
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
              @clear-items="onClearItems"
              @open-item-selector="openItemSelector"
              @toggle-stars="onToggleStars"
            />
          </div>

          <!-- Mobile synergies -->
          <div class="xl:hidden mb-4">
            <SynergyPanel
              :activeTraits="sortedActiveTraits"
              :horizontal="true"
            />
          </div>

          <!-- Champions below board -->
          <ChampionPanel
            :champions="tftData.champions"
            @select="onSelectChampion"
          />

          <!-- Disposition -->
          <div class="mt-4">
            <DispositionEditor v-model="formDispositions" :tftData="tftData" />
          </div>

          <!-- Notes -->
          <div class="mt-4 mb-8">
            <AppTextarea
              ref="notesTextarea"
              v-model="form.notes"
              @input="autoResizeTextarea"
              placeholder="Anotações sobre esta composição..."
              rows="5"
              spellcheck="false"
            />
          </div>
        </div>

        <!-- Right panel: Items -->
        <div class="w-72 flex-shrink-0">
          <ItemPanel :items="tftData.items" @select="onSelectItem" />
        </div>
      </div>
    </div>

    <!-- Champion selector modal -->
    <AppModal
      :show="championSelectorOpen"
      title="Selecionar Campeão"
      max-width="2xl"
      @close="closeChampionSelector"
    >
      <AppInput
        ref="championSelectorInput"
        v-model="championSelectorSearch"
        @keydown.enter.prevent="selectFirstChampion"
        type="text"
        placeholder="Buscar campeão..."
        class="w-full mb-4"
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
          <div
            class="w-full aspect-square bg-gray-800 rounded overflow-hidden"
            :style="{ borderBottom: `2px solid var(--cost-color)` }"
          >
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
    </AppModal>

    <!-- Item selector modal -->
    <AppModal
      :show="itemSelectorOpen"
      title="Selecionar Item"
      max-width="2xl"
      @close="closeItemSelector"
    >
      <AppInput
        ref="itemSelectorInput"
        v-model="itemSelectorSearch"
        @keydown.enter.prevent="selectFirstItem"
        type="text"
        placeholder="Buscar item..."
        class="w-full mb-4"
      />
      <div class="grid grid-cols-8 gap-2">
        <div
          v-for="item in filteredModalItems"
          :key="item.id"
          @click="selectItemFromModal(item)"
          class="item-grid-item w-10 h-10 cursor-pointer"
          :title="item.name"
        >
          <img
            :src="item.icon"
            :alt="item.name"
            class="w-full h-full object-cover rounded"
            loading="lazy"
          />
        </div>
      </div>
    </AppModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  ChevronLeftIcon,
  ArchiveBoxIcon,
  DocumentDuplicateIcon,
  ClipboardDocumentIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import AppInput from '@/Components/UI/AppInput.vue';
import AppTextarea from '@/Components/UI/AppTextarea.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import LevelTabs from '@/Components/LevelTabs.vue';
import HexBoard from '@/Components/HexBoard.vue';
import SynergyPanel from '@/Components/SynergyPanel.vue';
import ChampionPanel from '@/Components/ChampionPanel.vue';
import ItemPanel from '@/Components/ItemPanel.vue';
import DispositionEditor from '@/Components/DispositionEditor.vue';
import { useBoardState } from '@/composables/useBoardState';

const props = defineProps({
  composition: Object, // null for create
  levels: Array,
  dispositions: { type: Array, default: () => [] },
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
  ROWS,
  COLS,
  boardState,
  loadState,
  exportState,
  placeChampion,
  placeChampionAuto,
  removeChampion,
  moveChampion,
  toggleStars,
  addItem,
  removeItem,
  clearItems,
  championCount,
  activeTraits,
} = useBoardState(tftDataRef);

// Form data
const form = ref({
  name: props.composition?.name || '',
  notes: props.composition?.notes || '',
});

// Dispositions data
const formDispositions = ref(
  JSON.parse(JSON.stringify(props.dispositions || [])),
);

// Champion selector modal state
const championSelectorOpen = ref(false);
const championSelectorSearch = ref('');
const championSelectorTarget = ref(null); // { row, col }
const championSelectorInput = ref(null);

// Helper: determine if a board cell should count as a champion for the level counter
function isCountedChampionCell(cell) {
  if (!cell || !cell.championId) return false;

  const id = String(cell.championId).toLowerCase();

  // Galio should never count as a champion in the counter (but still counts for synergies)
  if (id.includes('galio')) return false;

  // If it's a summon, only Tibbers should be counted as a champion
  if (cell.isSummon) {
    const summonType = String(cell.summonType || '').toLowerCase();
    const isTibbers = summonType === 'tibbers' || id.includes('tibbers');
    return isTibbers;
  }

  return true;
}

// Item selector modal state
const itemSelectorOpen = ref(false);
const itemSelectorSearch = ref('');
const itemSelectorTarget = ref(null); // { row, col }
const itemSelectorInput = ref(null);

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
    const count = Object.values(state).filter((cell) =>
      isCountedChampionCell(cell),
    ).length;
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
    const state =
      lvl === activeLevel.value
        ? boardState.value
        : levelStates.value[lvl] || {};
    counts[lvl] = Object.values(state).filter((cell) =>
      isCountedChampionCell(cell),
    ).length;
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
  const el = notesTextarea.value?.el;
  if (!el) return;

  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}

// Champion placement from panel — auto-place in first empty cell
function onSelectChampion(champion) {
  placeChampionAuto(champion.id);
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
  } else {
    openChampionSelector({ row, col });
  }
}

function onToggleStars({ row, col }) {
  toggleStars(row, col);
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

function onClearItems({ row, col }) {
  clearItems(row, col);
}

function openChampionSelector({ row, col }) {
  championSelectorTarget.value = { row, col };
  championSelectorSearch.value = '';
  championSelectorOpen.value = true;
  nextTick(() => {
    if (championSelectorInput.value) championSelectorInput.value.focus();
  });
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
    const isOutsideBoard =
      event.clientX < boardRect.left ||
      event.clientX > boardRect.right ||
      event.clientY < boardRect.top ||
      event.clientY > boardRect.bottom;

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
  return props.tftData.champions.filter((champion) => {
    if (champion.isSummon) return false;
    if (search) {
      const nameMatch = champion.name.toLowerCase().includes(search);
      const traitMatch = champion.traits?.some((t) =>
        t.name?.toLowerCase().includes(search),
      );
      if (!nameMatch && !traitMatch) return false;
    }
    return true;
  });
});

function selectChampionFromModal(champion) {
  if (championSelectorTarget.value) {
    placeChampion(
      championSelectorTarget.value.row,
      championSelectorTarget.value.col,
      champion.id,
    );
  }
  closeChampionSelector();
}

function selectFirstChampion() {
  const list = filteredModalChampions.value || [];
  if (list.length > 0) {
    selectChampionFromModal(list[0]);
  }
}

function openItemSelector({ row, col }) {
  itemSelectorTarget.value = { row, col };
  itemSelectorSearch.value = '';
  itemSelectorOpen.value = true;
  nextTick(() => {
    if (itemSelectorInput.value) itemSelectorInput.value.focus();
  });
}

function closeItemSelector() {
  itemSelectorOpen.value = false;
  itemSelectorTarget.value = null;
}

const filteredModalItems = computed(() => {
  if (!props.tftData?.items) return [];
  const search = itemSelectorSearch.value.toLowerCase();
  return props.tftData.items.filter((item) => {
    if (search && !item.name.toLowerCase().includes(search)) return false;
    return true;
  });
});

function selectItemFromModal(item) {
  if (itemSelectorTarget.value) {
    addItem(
      itemSelectorTarget.value.row,
      itemSelectorTarget.value.col,
      item.id,
    );
  }
  closeItemSelector();
}

function selectFirstItem() {
  const list = filteredModalItems.value || [];
  if (list.length > 0) {
    selectItemFromModal(list[0]);
  }
}

// Save composition
function save() {
  saving.value = true;

  // Save current level state
  levelStates.value[activeLevel.value] = exportState();

  const data = {
    name: form.value.name || 'Composição sem nome',
    notes: form.value.notes || null,
    levels: LEVELS.map((level) => {
      const boardState = levelStates.value[level];
      // Convert to plain object to avoid Inertia converting {} to []
      const plainBoardState = boardState
        ? JSON.parse(JSON.stringify(boardState))
        : {};
      return {
        level,
        board_state: plainBoardState,
      };
    }),
    dispositions: formDispositions.value,
  };

  if (props.composition?.id) {
    router.put(route('compositions.update', props.composition.id), data, {
      onFinish: () => {
        saving.value = false;
      },
    });
  } else {
    router.post(route('compositions.store'), data, {
      onFinish: () => {
        saving.value = false;
      },
    });
  }
}
</script>
