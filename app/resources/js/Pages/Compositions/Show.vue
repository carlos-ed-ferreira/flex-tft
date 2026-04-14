<template>
  <AppLayout>
    <template #header-actions>
      <div class="flex items-center gap-2">
        <AppButton
          variant="secondary"
          @click="router.visit(route('compositions.index'))"
        >
          <ChevronLeftIcon class="w-4 h-4" />
          <span>Voltar</span>
        </AppButton>
        <button
          v-if="auth.user"
          @click="importComposition"
          class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition"
        >
          <ArrowDownTrayIcon class="w-4 h-4" />
          Importar para Minhas
        </button>
      </div>
    </template>

    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">{{ composition.name }}</h1>
        <p v-if="composition.author" class="text-sm text-gray-500 mt-1">
          por {{ composition.author }}
        </p>
      </div>

      <!-- Notes -->
      <div
        v-if="composition.notes"
        class="mb-6 bg-gray-900 border border-gray-800 rounded-xl p-4"
      >
        <p class="text-sm text-gray-300 whitespace-pre-wrap">
          {{ composition.notes }}
        </p>
      </div>

      <!-- Dispositions -->
      <div
        v-if="dispositions.length > 0"
        class="mb-6 bg-gray-900 border border-gray-800 rounded-xl p-4"
      >
        <h2
          class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3"
        >
          Disposições
        </h2>
        <div class="flex items-center gap-2 flex-wrap">
          <div
            v-for="(disp, dIdx) in dispositions"
            :key="dIdx"
            class="flex items-center gap-1 px-2.5 py-1 rounded-lg"
            :class="dispBgClass(disp.type)"
          >
            <template v-if="disp.type === 'champion'">
              <div class="flex items-center gap-0.5">
                <div
                  v-for="champId in (disp.champion_ids || []).slice(0, 4)"
                  :key="champId"
                  class="w-5 h-5 rounded-sm overflow-hidden"
                  :title="getChampionName(champId)"
                >
                  <img
                    :src="getChampionIcon(champId)"
                    :alt="getChampionName(champId)"
                    class="w-full h-full object-cover"
                  />
                </div>
                <span
                  v-if="(disp.champion_ids || []).length > 4"
                  class="text-[10px] text-gray-500"
                  >+{{ (disp.champion_ids || []).length - 4 }}</span
                >
              </div>
              <span class="text-xs font-medium text-purple-300">
                <template v-if="disp.star_level">{{
                  '★'.repeat(disp.star_level)
                }}</template>
              </span>
            </template>
            <template v-else-if="disp.type === 'trait'">
              <img
                v-if="getTraitIcon(disp.trait_id)"
                :src="getTraitIcon(disp.trait_id)"
                :alt="getTraitName(disp.trait_id)"
                class="w-5 h-5 object-contain"
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
                  class="w-5 h-5 rounded-sm overflow-hidden"
                  :title="getItemName(itemId)"
                >
                  <img
                    :src="getItemIcon(itemId)"
                    :alt="getItemName(itemId)"
                    class="w-full h-full object-cover"
                  />
                </div>
                <span
                  v-if="(disp.item_ids || []).length > 3"
                  class="text-[10px] text-gray-500"
                  >+{{ (disp.item_ids || []).length - 3 }}</span
                >
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- Level tabs -->
      <div class="flex gap-2 mb-4 overflow-x-auto">
        <button
          v-for="lvl in availableLevels"
          :key="lvl"
          @click="activeLevel = lvl"
          class="px-4 py-2 text-sm font-medium rounded-lg transition whitespace-nowrap"
          :class="
            activeLevel === lvl
              ? 'bg-blue-600 text-white'
              : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'
          "
        >
          Nível {{ lvl }}
          <span class="ml-1 text-xs opacity-70"
            >({{ getLevelChampionCount(lvl) }})</span
          >
        </button>
      </div>

      <!-- Active level board -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <div v-if="currentLevelChampions.length === 0" class="text-center py-8">
          <p class="text-gray-500">Nenhum campeão neste nível.</p>
        </div>
        <div v-else class="flex flex-wrap gap-4">
          <div
            v-for="cell in currentLevelChampions"
            :key="cell.key"
            class="flex flex-col items-center gap-1.5"
          >
            <!-- Champion icon -->
            <div class="relative">
              <div
                class="w-14 h-14 rounded-lg overflow-hidden border-2"
                :class="`cost-${getChampionCost(cell.championId)}`"
                :style="{ borderColor: 'var(--cost-color)' }"
              >
                <img
                  :src="getChampionIcon(cell.championId)"
                  :alt="getChampionName(cell.championId)"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
              </div>
              <!-- Stars -->
              <div
                v-if="cell.stars && cell.stars > 1"
                class="absolute -top-1 left-1/2 -translate-x-1/2 text-yellow-400 text-[10px] leading-none"
              >
                {{ '★'.repeat(cell.stars) }}
              </div>
            </div>
            <!-- Champion name -->
            <span
              class="text-[11px] text-gray-400 text-center max-w-[60px] truncate"
            >
              {{ getChampionName(cell.championId) }}
            </span>
            <!-- Items -->
            <div
              v-if="cell.items && cell.items.length > 0"
              class="flex gap-0.5"
            >
              <div
                v-for="(itemId, idx) in cell.items"
                :key="idx"
                class="w-6 h-6 rounded-sm overflow-hidden bg-gray-800"
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

      <!-- CTA for guests -->
      <div v-if="!auth.user" class="mt-8 text-center">
        <p class="text-gray-400 mb-3">
          Crie uma conta para importar esta composição e montar as suas
          próprias.
        </p>
        <Link
          :href="route('register')"
          class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition"
        >
          Criar Conta
        </Link>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import { ChevronLeftIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  composition: Object,
  levels: Array,
  dispositions: { type: Array, default: () => [] },
  tftData: Object,
});

const auth = computed(() => usePage().props.auth);

// Find which levels have content
const availableLevels = computed(() => {
  return props.levels
    .filter((l) => {
      const state = l.board_state;
      if (!state || typeof state !== 'object') return false;
      return Object.keys(state).length > 0;
    })
    .map((l) => l.level);
});

// Default to highest populated level
const activeLevel = ref(null);

const highestLevel = computed(() => {
  return availableLevels.value.length > 0
    ? availableLevels.value[availableLevels.value.length - 1]
    : props.levels?.[0]?.level || 3;
});

if (availableLevels.value.length > 0) {
  activeLevel.value = highestLevel.value;
} else {
  activeLevel.value = 3;
}

// Get champions from active level's board_state
const currentLevelChampions = computed(() => {
  const level = props.levels.find((l) => l.level === activeLevel.value);
  if (!level || !level.board_state) return [];

  const state = level.board_state;
  return Object.entries(state)
    .filter(([, cell]) => cell && cell.championId)
    .map(([key, cell]) => ({
      key,
      championId: cell.championId,
      stars: cell.stars || 1,
      items: cell.items || [],
    }));
});

function getLevelChampionCount(lvl) {
  const level = props.levels.find((l) => l.level === lvl);
  if (!level || !level.board_state) return 0;
  return Object.values(level.board_state).filter(
    (cell) => cell && cell.championId,
  ).length;
}

// Maps
const championsMap = computed(() => {
  const map = {};
  (props.tftData?.champions || []).forEach((champ) => {
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
  (props.tftData?.items || []).forEach((item) => {
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
  (props.tftData?.traits || []).forEach((t) => {
    map[t.id] = t;
  });
  return map;
});

function getTraitIcon(traitId) {
  return traitsMap.value[traitId]?.icon || '';
}
function getTraitName(traitId) {
  return traitsMap.value[traitId]?.name || traitId;
}

function dispBgClass(type) {
  return (
    {
      champion: 'bg-purple-900/30',
      trait: 'bg-amber-900/30',
      item: 'bg-blue-900/30',
    }[type] || 'bg-gray-800/50'
  );
}

function importComposition() {
  router.post(route('compositions.import', props.composition.id));
}
</script>
