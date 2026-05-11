<template>
  <AppLayout>
    <template #header-links>
      <Link
        :href="route('compositions.index')"
        class="text-md text-white hover:text-yellow-400 transition"
      >
        Composições Recomendadas
      </Link>

      <Link
        :href="route('compositions.my')"
        class="text-md text-white hover:text-yellow-400 transition"
      >
        Minhas Composições
      </Link>

      <Link
        :href="route('simulator.index')"
        class="text-md text-white hover:text-yellow-400 transition"
      >
        Simular Caminhos
      </Link>
    </template>

    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="mb-4 flex items-center gap-3">
        <AppInput
          v-model="form.name"
          type="text"
          placeholder="Nome da composição..."
          class="flex-1 bg-transparent border-0 border-b border-gray-700 focus:border-blue-500 text-2xl font-bold text-white placeholder-gray-600 px-0 py-2 rounded-none"
        />
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

      <div class="flex gap-4 mt-4">
        <div class="w-56 flex-shrink-0 hidden xl:block">
          <SynergyPanel :activeTraits="activeTraits" />
        </div>

        <div class="flex-1 flex flex-col">
          <div class="mb-2 flex justify-center">
            <LevelTabs
              :levels="availableLevels"
              :activeLevel="activeLevel"
              :levelChampionCounts="levelChampionCounts"
              @select="switchLevel"
            />
          </div>

          <div
            class="mb-4 flex flex-col items-center justify-center gap-2 lg:flex-row lg:gap-3"
          >
            <VersionTabs
              v-if="activeLevel !== null"
              class="lg:w-auto"
              :versions="versionsForActiveLevel"
              :activeVersion="activeVersion"
              :level="activeLevel"
              @select="switchVersion"
              @add="addVersion"
              @delete="deleteVersion"
              @rename="renameVersion"
            />
            <div
              class="flex min-h-9 shrink-0 items-center gap-1 rounded-lg border border-gray-800 bg-gray-950/80 p-1 shadow-inner shadow-black/30"
            >
              <AppButton
                @click="copyLevel"
                variant="secondary"
                size="sm"
                class="!rounded-md !border !border-gray-700 !bg-gray-950 !px-2.5 !py-1.5 !text-gray-200 hover:!border-blue-700 hover:!bg-gray-900 hover:!text-white"
                title="Copiar composição atual"
              >
                <DocumentDuplicateIcon class="w-4 h-4" />
                <span class="hidden sm:inline">Copiar</span>
              </AppButton>
              <AppButton
                @click="pasteLevel"
                :disabled="!clipboard"
                variant="secondary"
                size="sm"
                class="!rounded-md !border !border-gray-700 !bg-gray-950 !px-2.5 !py-1.5 !text-gray-200 hover:!border-blue-700 hover:!bg-gray-900 hover:!text-white disabled:!text-gray-500"
                title="Colar composição copiada"
              >
                <ClipboardDocumentIcon class="w-4 h-4" />
                <span class="hidden sm:inline">Colar</span>
              </AppButton>
              <AppButton
                @click="clearLevel"
                variant="warning"
                size="sm"
                class="!rounded-md !border !border-amber-800/70 !bg-amber-950/70 !px-2.5 !py-1.5 !text-amber-200 hover:!border-amber-700 hover:!bg-amber-900/70 hover:!text-amber-100"
                title="Limpar composição atual"
              >
                <IconEraser class="h-4 w-4" />
                <span class="hidden sm:inline">Limpar</span>
              </AppButton>
            </div>
          </div>

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

          <div class="xl:hidden mb-4">
            <SynergyPanel :activeTraits="activeTraits" :horizontal="true" />
          </div>

          <ChampionPanel
            :champions="tftData.champions"
            @select="onSelectChampion"
          />

          <div class="mt-4">
            <DispositionEditor v-model="formDispositions" :tftData="tftData" />
          </div>

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

        <div class="w-72 flex-shrink-0">
          <ItemPanel :items="tftData.items" @select="onSelectItem" />
        </div>
      </div>
    </div>

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
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { IconEraser } from '@tabler/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  ArchiveBoxIcon,
  DocumentDuplicateIcon,
  ClipboardDocumentIcon,
} from '@heroicons/vue/24/outline';
import AppInput from '@/Components/UI/AppInput.vue';
import AppTextarea from '@/Components/UI/AppTextarea.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import LevelTabs from '@/Components/LevelTabs.vue';
import VersionTabs from '@/Components/VersionTabs.vue';
import HexBoard from '@/Components/HexBoard.vue';
import SynergyPanel from '@/Components/SynergyPanel.vue';
import ChampionPanel from '@/Components/ChampionPanel.vue';
import ItemPanel from '@/Components/ItemPanel.vue';
import DispositionEditor from '@/Components/DispositionEditor.vue';
import { useBoardState } from '@/composables/useBoardState';

const props = defineProps({
  composition: Object,
  levels: Array,
  dispositions: { type: Array, default: () => [] },
  tftData: Object,
});

const availableLevels = computed(() =>
  (props.levels || []).map((levelGroup) => levelGroup.level),
);
const activeLevel = ref(null);
const activeVersion = ref(1);
const saving = ref(false);

const levelStates = ref({});
const levelVersionLabels = ref({});
const lastActiveVersionPerLevel = ref({});
const tftDataRef = computed(() => props.tftData);

const clipboard = ref(null);

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
  activeTraits,
} = useBoardState(tftDataRef);

const form = ref({
  name: props.composition?.name || '',
  notes: props.composition?.notes || '',
});

const formDispositions = ref(
  JSON.parse(JSON.stringify(props.dispositions || [])),
);

const championSelectorOpen = ref(false);
const championSelectorSearch = ref('');
const championSelectorTarget = ref(null);
const championSelectorInput = ref(null);

function isCountedChampionCell(cell) {
  if (!cell || !cell.championId) return false;

  const id = String(cell.championId).toLowerCase();

  if (id.includes('galio')) return false;

  if (cell.isSummon) {
    const summonType = String(cell.summonType || '').toLowerCase();
    const isTibbers = summonType === 'tibbers' || id.includes('tibbers');
    return isTibbers;
  }

  return true;
}

const itemSelectorOpen = ref(false);
const itemSelectorSearch = ref('');
const itemSelectorTarget = ref(null);
const itemSelectorInput = ref(null);

const selectedChampion = ref(null);

const selectedItem = ref(null);

onMounted(() => {
  for (const levelGroup of props.levels) {
    levelStates.value[levelGroup.level] = {};
    levelVersionLabels.value[levelGroup.level] = {};
    for (const v of levelGroup.versions) {
      levelStates.value[levelGroup.level][v.version] = v.board_state || {};
      levelVersionLabels.value[levelGroup.level][v.version] = v.label ?? null;
    }
  }

  let highestGreenLevel = availableLevels.value[0];
  for (let i = availableLevels.value.length - 1; i >= 0; i--) {
    const lvl = availableLevels.value[i];
    const state = (levelStates.value[lvl] || {})[1] || {};
    const count = Object.values(state).filter((cell) =>
      isCountedChampionCell(cell),
    ).length;
    if (count === lvl) {
      highestGreenLevel = lvl;
      break;
    }
  }

  activeLevel.value = highestGreenLevel;
  activeVersion.value = 1;
  loadState(
    (levelStates.value[activeLevel.value] || {})[activeVersion.value] || {},
  );

  if (notesTextarea.value) {
    autoResizeTextarea();
  }
});

const levelChampionCounts = computed(() => {
  const counts = {};
  for (const lvl of availableLevels.value) {
    if (lvl === activeLevel.value) {
      counts[lvl] = Object.values(boardState.value).filter((cell) =>
        isCountedChampionCell(cell),
      ).length;
    } else {
      const v1State = (levelStates.value[lvl] || {})[1] || {};
      counts[lvl] = Object.values(v1State).filter((cell) =>
        isCountedChampionCell(cell),
      ).length;
    }
  }
  return counts;
});

const versionsForActiveLevel = computed(() => {
  if (activeLevel.value === null) return [];
  const states = levelStates.value[activeLevel.value] || {};
  const labels = levelVersionLabels.value[activeLevel.value] || {};
  return Object.keys(states)
    .map(Number)
    .sort((a, b) => a - b)
    .map((v) => ({ version: v, label: labels[v] ?? null }));
});

function switchLevel(newLevel) {
  levelStates.value[activeLevel.value][activeVersion.value] = exportState();
  lastActiveVersionPerLevel.value[activeLevel.value] = activeVersion.value;

  activeLevel.value = newLevel;
  const restoredVersion =
    lastActiveVersionPerLevel.value[newLevel] ||
    Math.min(
      ...Object.keys(levelStates.value[newLevel] || { 1: {} }).map(Number),
    );
  activeVersion.value = restoredVersion;
  loadState((levelStates.value[newLevel] || {})[restoredVersion] || {});
}

function switchVersion(newVersion) {
  levelStates.value[activeLevel.value][activeVersion.value] = exportState();
  lastActiveVersionPerLevel.value[activeLevel.value] = newVersion;
  activeVersion.value = newVersion;
  loadState((levelStates.value[activeLevel.value] || {})[newVersion] || {});
}

function addVersion() {
  const currentStates = levelStates.value[activeLevel.value] || {};
  const nextVersion = Math.max(...Object.keys(currentStates).map(Number)) + 1;

  levelStates.value[activeLevel.value][activeVersion.value] = exportState();
  levelStates.value[activeLevel.value][nextVersion] = JSON.parse(
    JSON.stringify(exportState()),
  );
  levelVersionLabels.value[activeLevel.value][nextVersion] = null;

  switchVersion(nextVersion);
}

function deleteVersion(version) {
  const currentStates = levelStates.value[activeLevel.value] || {};
  if (Object.keys(currentStates).length <= 1) return;

  const wasActive = activeVersion.value === version;

  delete levelStates.value[activeLevel.value][version];
  delete levelVersionLabels.value[activeLevel.value][version];

  if (wasActive) {
    const remaining = Object.keys(levelStates.value[activeLevel.value])
      .map(Number)
      .sort((a, b) => a - b);
    const fallback = remaining[0];
    activeVersion.value = fallback;
    loadState((levelStates.value[activeLevel.value] || {})[fallback] || {});
    lastActiveVersionPerLevel.value[activeLevel.value] = fallback;
  }
}

function renameVersion(version, label) {
  if (!levelVersionLabels.value[activeLevel.value]) {
    levelVersionLabels.value[activeLevel.value] = {};
  }
  levelVersionLabels.value[activeLevel.value][version] = label || null;
}

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

function autoResizeTextarea() {
  const el = notesTextarea.value?.el;
  if (!el) return;

  el.style.height = 'auto';
  el.style.height = el.scrollHeight + 'px';
}

function onSelectChampion(champion) {
  placeChampionAuto(champion.id);
}

function onSelectItem(item) {
  selectedItem.value = item;
  selectedChampion.value = null;
}

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

const hexBoard = ref(null);

function onBoardAreaDragOver(event) {
  event.preventDefault();
}

function onBoardAreaDrop(event) {
  event.preventDefault();

  const cellData = event.dataTransfer.getData('application/tft-cell');
  if (!cellData) return;

  const { fromRow, fromCol, type } = JSON.parse(cellData);
  if (type !== 'board-champion') return;

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

function save() {
  saving.value = true;

  levelStates.value[activeLevel.value][activeVersion.value] = exportState();

  const flatLevels = [];
  for (const [lvl, versions] of Object.entries(levelStates.value)) {
    for (const [ver, state] of Object.entries(versions)) {
      const label = (levelVersionLabels.value[lvl] || {})[Number(ver)] ?? null;
      const plainBoardState = state ? JSON.parse(JSON.stringify(state)) : {};
      flatLevels.push({
        level: Number(lvl),
        version: Number(ver),
        label,
        board_state: plainBoardState,
      });
    }
  }

  const data = {
    name: form.value.name || 'Composição sem nome',
    notes: form.value.notes || null,
    levels: flatLevels,
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
