<template>
  <AppLayout>
    <template #header-actions>
      <Link
        :href="route('compositions.index')"
        class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium rounded-lg transition flex items-center gap-2"
      >
        <ChevronLeftIcon class="w-4 h-4 flex-shrink-0 self-center" />
        Voltar
      </Link>
    </template>

    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-2xl font-bold text-white mb-6">Simulador de caminhos</h1>

      <div class="mb-4 flex items-center gap-2">
        <AppInput
          ref="searchInput"
          v-model="searchQuery"
          @keydown.enter.prevent="selectFirstSearchResult"
          type="text"
          placeholder="Busque por um campeão ou item..."
          class="flex-1 bg-gray-900 border-gray-700"
        />
        <AppButton
          variant="secondary"
          @click="clearAll"
          class="shrink-0 border border-gray-700"
        >
          <ArrowPathIcon class="w-4 h-4" />
          <span>Limpar tudo</span>
        </AppButton>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
          <h2
            class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1 pl-2 pt-1"
          >
            Campeões disponíveis
          </h2>

          <div
            class="grid grid-cols-9 gap-2 max-h-[360px] overflow-y-auto p-2"
            style="grid-template-rows: repeat(6, minmax(0, 1fr))"
          >
            <button
              v-for="champ in filteredChampions"
              :key="champ.id"
              type="button"
              class="champion-grid-item cursor-pointer"
              style="cursor: pointer"
              :class="`cost-${champ.cost}`"
              :title="`${champ.name} ($${champ.cost})`"
              @click="cycleChampionStars(champ.id)"
              @contextmenu.prevent="clearChampion(champ.id)"
            >
              <div
                class="relative w-full aspect-square bg-gray-800 rounded overflow-hidden border"
                :class="
                  isChampionActive(champ.id)
                    ? 'border-gray-600'
                    : 'border-gray-700'
                "
                :style="{
                  borderBottom: `2px solid var(--cost-color)`,
                  opacity: isChampionActive(champ.id) ? 1 : 0.28,
                }"
              >
                <img
                  v-if="champ.icon"
                  :src="champ.icon"
                  :alt="champ.name"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <span
                  v-if="getChampionStars(champ.id) > 0"
                  class="absolute bottom-0 right-0 px-1 py-0.5 text-[10px] font-bold leading-none text-yellow-300 bg-black/75 rounded-tl"
                  >{{ '★'.repeat(getChampionStars(champ.id)) }}</span
                >
              </div>
            </button>
          </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
          <h2
            class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1 pl-2 pt-2"
          >
            Componentes & Emblemas
          </h2>

          <div class="grid grid-cols-8 sm:grid-cols-9 gap-1.5 p-2">
            <button
              v-for="item in filteredComponentItems"
              :key="item.id"
              type="button"
              class="relative rounded-md overflow-hidden border bg-gray-800 transition hover:ring-2 hover:ring-blue-500 hover:scale-105"
              :class="
                isItemActive(item.id) ? 'border-gray-600' : 'border-gray-700'
              "
              :title="item.name"
              @click="increaseItemQuantity(item.id)"
              @contextmenu.prevent="decreaseItemQuantity(item.id)"
            >
              <img
                :src="item.icon"
                :alt="item.name"
                class="w-full aspect-square object-cover"
                :style="{
                  opacity: isItemActive(item.id) ? 1 : 0.28,
                }"
                loading="lazy"
              />
              <span
                v-if="getItemQuantity(item.id) > 0"
                class="absolute top-0 right-0 min-w-4 h-4 px-1 text-[10px] font-bold leading-4 text-white bg-blue-600 rounded-bl"
                >{{ getItemQuantity(item.id) }}</span
              >
            </button>
          </div>

          <div class="grid grid-cols-8 sm:grid-cols-9 gap-1.5 p-2">
            <button
              v-for="item in filteredEmblemItems"
              :key="item.id"
              type="button"
              class="relative rounded-md overflow-hidden border bg-gray-800 transition hover:ring-2 hover:ring-blue-500 hover:scale-105"
              :class="
                isItemActive(item.id) ? 'border-gray-600' : 'border-gray-700'
              "
              :title="item.name"
              @click="increaseItemQuantity(item.id)"
              @contextmenu.prevent="decreaseItemQuantity(item.id)"
            >
              <img
                :src="item.icon"
                :alt="item.name"
                class="w-full aspect-square object-cover"
                :style="{
                  opacity: isItemActive(item.id) ? 1 : 0.28,
                }"
                loading="lazy"
              />
              <span
                v-if="getItemQuantity(item.id) > 0"
                class="absolute top-0 right-0 min-w-4 h-4 px-1 text-[10px] font-bold leading-4 text-white bg-blue-600 rounded-bl"
                >{{ getItemQuantity(item.id) }}</span
              >
            </button>
          </div>
        </div>
      </div>

      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div>
            <h3 class="text-xs text-gray-500 uppercase tracking-wider mb-2">
              Sinergias possíveis
            </h3>
            <div v-if="activeTraits.length > 0" class="flex flex-wrap gap-2">
              <div
                v-for="trait in activeTraits"
                :key="trait.id"
                class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg border"
                :class="traitBadgeClass(trait)"
              >
                <img
                  v-if="trait.icon"
                  :src="trait.icon"
                  class="w-4 h-4 object-contain"
                />
                <span class="text-xs font-medium"
                  >{{ trait.name }} ({{ trait.count }})</span
                >
              </div>
            </div>
            <p v-else class="text-xs text-gray-600">
              Sem sinergias ativas no momento.
            </p>
          </div>

          <div>
            <h3 class="text-xs text-gray-500 uppercase tracking-wider mb-2">
              Itens possíveis
            </h3>
            <div
              v-if="craftableItems.length > 0"
              class="flex flex-wrap gap-1.5"
            >
              <div
                v-for="item in craftableItems"
                :key="item.id"
                class="w-8 h-8 rounded-sm overflow-hidden border border-gray-700"
                :title="item.name"
              >
                <img
                  :src="item.icon"
                  :alt="item.name"
                  class="w-full h-full object-cover"
                />
              </div>
            </div>
            <p v-else class="text-xs text-gray-600">
              Sem combinações montáveis com os componentes atuais.
            </p>
          </div>
        </div>
      </div>

      <div v-if="results.length > 0">
        <h2 class="text-lg font-semibold text-white mb-4">
          Melhores composições
        </h2>
        <div class="space-y-4">
          <div
            v-for="(result, rIdx) in results"
            :key="result.id"
            class="bg-gray-900 border rounded-xl p-5 transition"
            :class="rIdx === 0 ? 'border-yellow-600/50' : 'border-gray-800'"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <span
                  class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                  :class="{
                    'bg-yellow-600/20 text-yellow-400': rIdx === 0,
                    'bg-gray-700/50 text-gray-400': rIdx === 1,
                    'bg-amber-900/30 text-amber-500': rIdx === 2,
                  }"
                >
                  {{ rIdx + 1 }}o
                </span>
                <h3 class="text-lg font-semibold text-white">
                  {{ result.name }}
                </h3>
              </div>
              <div class="flex items-center gap-2">
                <span
                  class="text-2xl font-bold"
                  :class="{
                    'text-green-400': result.score >= 70,
                    'text-yellow-400': result.score >= 40 && result.score < 70,
                    'text-red-400': result.score < 40,
                  }"
                >
                  {{ result.score }}%
                </span>
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              <div
                v-for="(match, mIdx) in result.matches"
                :key="mIdx"
                class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium"
                :class="{
                  'bg-green-900/30 text-green-400': match.percent >= 80,
                  'bg-yellow-900/30 text-yellow-400':
                    match.percent >= 40 && match.percent < 80,
                  'bg-red-900/30 text-red-400': match.percent < 40,
                }"
              >
                <template v-if="match.type === 'champion'">
                  <div class="flex items-center gap-0.5">
                    <div
                      v-for="cid in (match.champion_ids || []).slice(0, 3)"
                      :key="cid"
                      class="w-4 h-4 rounded-sm overflow-hidden"
                    >
                      <img
                        :src="getChampionIcon(cid)"
                        class="w-full h-full object-cover"
                      />
                    </div>
                  </div>
                  <span>{{ match.percent }}%</span>
                </template>
                <template v-else-if="match.type === 'trait'">
                  <img
                    v-if="getTraitIcon(match.trait_id)"
                    :src="getTraitIcon(match.trait_id)"
                    class="w-4 h-4 object-contain"
                  />
                  <span
                    >{{ getTraitName(match.trait_id) }}
                    {{ match.percent }}%</span
                  >
                </template>
                <template v-else-if="match.type === 'item'">
                  <div class="flex items-center gap-0.5">
                    <div
                      v-for="iid in (match.item_ids || []).slice(0, 3)"
                      :key="iid"
                      class="w-4 h-4 rounded-sm overflow-hidden"
                    >
                      <img
                        :src="getItemIcon(iid)"
                        class="w-full h-full object-cover"
                      />
                    </div>
                  </div>
                  <span>{{ match.percent }}%</span>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="hasSelections && results.length === 0"
        class="text-center py-12"
      >
        <p class="text-gray-500">Nenhuma composição compatível encontrada.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import AppInput from '@/Components/UI/AppInput.vue';
import AppButton from '@/Components/UI/AppButton.vue';

const props = defineProps({
  compositions: { type: Array, default: () => [] },
  tftData: {
    type: Object,
    default: () => ({ champions: [], traits: [], items: [] }),
  },
});

const searchQuery = ref('');
const searchInput = ref(null);

const championStars = ref({});
const itemQuantities = ref({});

const championsMap = computed(() => {
  const map = {};
  props.tftData.champions.forEach((c) => {
    map[c.id] = c;
  });
  return map;
});

const itemsMap = computed(() => {
  const map = {};
  props.tftData.items.forEach((i) => {
    map[i.id] = i;
  });
  return map;
});

const traitsMap = computed(() => {
  const map = {};
  (props.tftData.traits || []).forEach((t) => {
    map[t.id] = t;
  });
  return map;
});

const traitNameToId = computed(() => {
  const map = {};
  (props.tftData.traits || []).forEach((t) => {
    map[t.name] = t.id;
  });
  return map;
});

function getChampionIcon(id) {
  return championsMap.value[id]?.icon || '';
}
function getItemIcon(id) {
  return itemsMap.value[id]?.icon || '';
}
function getTraitIcon(id) {
  return traitsMap.value[id]?.icon || '';
}
function getTraitName(id) {
  return traitsMap.value[id]?.name || id;
}
function normalizeId(id) {
  return String(id);
}

const filteredChampions = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();

  return props.tftData.champions
    .filter((champ) => [1, 2, 3].includes(champ.cost))
    .filter((champ) => {
      if (!q) return true;
      const byName = champ.name.toLowerCase().includes(q);
      const byTrait = (champ.traits || []).some((t) =>
        t.name.toLowerCase().includes(q),
      );
      return byName || byTrait;
    })
    .sort((a, b) => a.cost - b.cost || a.name.localeCompare(b.name));
});

function getChampionStars(championId) {
  return championStars.value[normalizeId(championId)] || 0;
}

function isChampionActive(championId) {
  return getChampionStars(championId) > 0;
}

function cycleChampionStars(championId) {
  const key = normalizeId(championId);
  const current = getChampionStars(championId);

  if (current === 0) {
    championStars.value[key] = 1;
    return;
  }

  if (current === 1) {
    championStars.value[key] = 2;
    return;
  }

  delete championStars.value[key];
}

function clearChampion(championId) {
  delete championStars.value[normalizeId(championId)];
}

const activeTraits = computed(() => {
  const counts = {};
  const activeChampions = Object.entries(championStars.value).filter(
    ([, stars]) => stars > 0,
  );

  activeChampions.forEach(([champId]) => {
    const champ = championsMap.value[champId];
    if (!champ) return;

    (champ.traits || []).forEach((t) => {
      const traitId = traitNameToId.value[t.name];
      if (traitId) {
        counts[traitId] = (counts[traitId] || 0) + 1;
      }
    });
  });

  return Object.entries(counts)
    .map(([id, count]) => {
      const trait = traitsMap.value[id] || {};
      const breakpoints = trait.breakpoints || [];
      const reachedBreakpoints = breakpoints
        .filter((bp) => count >= (bp.min || 0))
        .sort((a, b) => (a.min || 0) - (b.min || 0));
      const activeTier =
        reachedBreakpoints[reachedBreakpoints.length - 1] || null;

      return {
        id,
        name: trait.name || id,
        icon: trait.icon || '',
        count,
        activeTier,
        tierLevel: reachedBreakpoints.length,
      };
    })
    .sort((a, b) => {
      const styleOrder = { kChromatic: 4, kGold: 3, kSilver: 2, kBronze: 1 };
      const styleA = styleOrder[a.activeTier?.style] || 0;
      const styleB = styleOrder[b.activeTier?.style] || 0;
      return styleB - styleA || b.tierLevel - a.tierLevel || b.count - a.count;
    });
});

function traitBadgeClass(trait) {
  const style = trait.activeTier?.style;
  if (style === 'kChromatic')
    return 'bg-gradient-to-r from-pink-700/50 via-yellow-600/40 to-cyan-700/50 border-cyan-600/50 text-white';
  if (style === 'kGold')
    return 'bg-yellow-700/25 border-yellow-500/40 text-yellow-200';
  if (style === 'kSilver') return 'bg-white/20 border-white/60 text-white';
  if (style === 'kBronze')
    return 'bg-amber-800/25 border-amber-600/40 text-amber-200';

  if (trait.tierLevel >= 4)
    return 'bg-fuchsia-700/25 border-fuchsia-500/40 text-fuchsia-200';
  if (trait.tierLevel === 3)
    return 'bg-indigo-700/25 border-indigo-500/40 text-indigo-200';
  if (trait.tierLevel === 2)
    return 'bg-sky-700/25 border-sky-500/40 text-sky-200';
  if (trait.tierLevel === 1)
    return 'bg-emerald-700/25 border-emerald-500/40 text-emerald-200';
  return 'bg-gray-800/60 border-gray-700/70 text-gray-400 opacity-85';
}

function clearAll() {
  searchQuery.value = '';
  championStars.value = {};
  itemQuantities.value = {};
  searchInput.value?.focus();
}

const componentItems = computed(() =>
  props.tftData.items
    .filter((i) => i.category === 'component')
    .sort((a, b) => a.name.localeCompare(b.name)),
);

const emblemItems = computed(() =>
  props.tftData.items
    .filter((i) => i.category === 'emblem')
    .sort((a, b) => a.name.localeCompare(b.name)),
);

const filteredComponentItems = computed(() => {
  if (!searchQuery.value) return componentItems.value;
  const q = searchQuery.value.toLowerCase();
  return componentItems.value.filter((i) => i.name.toLowerCase().includes(q));
});

const filteredEmblemItems = computed(() => {
  if (!searchQuery.value) return emblemItems.value;
  const q = searchQuery.value.toLowerCase();
  return emblemItems.value.filter((i) => i.name.toLowerCase().includes(q));
});

function getItemQuantity(itemId) {
  return itemQuantities.value[normalizeId(itemId)] || 0;
}

function isItemActive(itemId) {
  return getItemQuantity(itemId) > 0;
}

function increaseItemQuantity(itemId) {
  const key = normalizeId(itemId);
  itemQuantities.value[key] = getItemQuantity(key) + 1;
}

function decreaseItemQuantity(itemId) {
  const key = normalizeId(itemId);
  const nextQty = getItemQuantity(key) - 1;
  if (nextQty <= 0) {
    delete itemQuantities.value[key];
    return;
  }

  itemQuantities.value[key] = nextQty;
}

const combinedSearchResults = computed(() => {
  return [
    ...filteredChampions.value.map((champion) => ({
      type: 'champion',
      id: champion.id,
    })),
    ...filteredComponentItems.value.map((item) => ({
      type: 'item',
      id: item.id,
    })),
    ...filteredEmblemItems.value.map((item) => ({ type: 'item', id: item.id })),
  ];
});

function selectFirstSearchResult() {
  if (!searchQuery.value.trim()) return;

  const first = combinedSearchResults.value[0];
  if (!first) return;

  if (first.type === 'champion') {
    cycleChampionStars(first.id);
  } else {
    increaseItemQuantity(first.id);
  }

  searchQuery.value = '';
  searchInput.value?.focus();
}

const hasSelections = computed(() => {
  const hasChampion = Object.values(championStars.value).some(
    (stars) => stars > 0,
  );
  const hasItem = Object.values(itemQuantities.value).some((qty) => qty > 0);
  return hasChampion || hasItem;
});

function getActiveItemIds() {
  return Object.entries(itemQuantities.value)
    .filter(([, qty]) => qty > 0)
    .map(([id]) => id);
}

function getAvailableComponentPool() {
  const pool = [];

  for (const [itemId, qty] of Object.entries(itemQuantities.value)) {
    if (qty <= 0) continue;
    if (itemsMap.value[itemId]?.category !== 'component') continue;

    for (let i = 0; i < qty; i += 1) {
      pool.push(itemId);
    }
  }

  return pool;
}

const craftableItems = computed(() => {
  const availableComponents = getAvailableComponentPool();
  const combinedItems = props.tftData.items.filter(
    (i) => i.category === 'combined' && i.recipe && i.recipe.length === 2,
  );
  const craftable = [];

  for (const item of combinedItems) {
    if (canCraftItem(item.recipe, availableComponents)) {
      craftable.push(item);
    }
  }

  return craftable.sort((a, b) => a.name.localeCompare(b.name));
});

function canCraftItem(recipe, availablePool) {
  if (!recipe || recipe.length !== 2) return false;

  const pool = [...availablePool];
  for (const compId of recipe.map(normalizeId)) {
    const idx = pool.indexOf(compId);
    if (idx === -1) return false;
    pool.splice(idx, 1);
  }

  return true;
}

const results = computed(() => {
  if (!hasSelections.value) return [];

  const scored = props.compositions.map((comp) => {
    const { score, matches } = scoreComposition(comp);

    return {
      id: comp.id,
      name: comp.name,
      score: Math.round(score),
      matches,
    };
  });

  scored.sort((a, b) => b.score - a.score);
  return scored.slice(0, 5).filter((r) => r.score > 0);
});

function scoreComposition(comp) {
  const dispositions = comp.dispositions || [];
  if (dispositions.length === 0) return { score: 0, matches: [] };

  const matches = dispositions.map((disp) => {
    const percent = scoreDisposition(disp);

    return {
      type: disp.type,
      champion_ids: disp.champion_ids,
      trait_id: disp.trait_id,
      item_ids: disp.item_ids,
      percent: Math.round(percent),
    };
  });

  const totalWeight = dispositions.reduce(
    (sum, d) => sum + (d.priority || 1),
    0,
  );
  const weightedScore = dispositions.reduce((sum, d, i) => {
    return sum + matches[i].percent * (d.priority || 1);
  }, 0);

  return {
    score: totalWeight > 0 ? weightedScore / totalWeight : 0,
    matches,
  };
}

function scoreDisposition(disp) {
  switch (disp.type) {
    case 'champion':
      return scoreChampionDisp(disp);
    case 'trait':
      return scoreTraitDisp(disp);
    case 'item':
      return scoreItemDisp(disp);
    default:
      return 0;
  }
}

function scoreChampionDisp(disp) {
  const ids = disp.champion_ids || [];
  if (ids.length === 0) return 0;
  const normalizedIds = ids.map(normalizeId);

  for (const [championId, stars] of Object.entries(championStars.value)) {
    if (stars <= 0) continue;
    if (!normalizedIds.includes(championId)) continue;

    if (disp.star_level && stars >= disp.star_level) {
      return 100;
    }

    if (disp.star_level && stars < disp.star_level) {
      return 70;
    }

    return 100;
  }

  return 0;
}

function scoreTraitDisp(disp) {
  if (!disp.trait_id) return 0;
  const requiredCount = disp.trait_count || 1;

  const activeTrait = activeTraits.value.find(
    (t) => normalizeId(t.id) === normalizeId(disp.trait_id),
  );
  if (!activeTrait) return 0;

  if (activeTrait.count >= requiredCount) {
    return 100;
  }

  return Math.round((activeTrait.count / requiredCount) * 70);
}

function scoreItemDisp(disp) {
  const wantedItemIds = disp.item_ids || [];
  if (wantedItemIds.length === 0) return 0;

  const selectedItemIds = getActiveItemIds();
  const availableComponents = getAvailableComponentPool();

  for (const wantedId of wantedItemIds) {
    const item = itemsMap.value[wantedId];
    if (!item) continue;

    if (selectedItemIds.includes(normalizeId(wantedId))) {
      return 100;
    }

    if (
      item.recipe &&
      item.recipe.length === 2 &&
      canCraftItem(item.recipe, availableComponents)
    ) {
      return 100;
    }
  }

  for (const wantedId of wantedItemIds) {
    const item = itemsMap.value[wantedId];
    if (!item || !item.recipe || item.recipe.length !== 2) continue;

    for (const compId of item.recipe) {
      if (availableComponents.includes(normalizeId(compId))) {
        return 50;
      }
    }
  }

  return 0;
}
</script>
