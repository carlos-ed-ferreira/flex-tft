<template>
    <AppLayout>
        <template #header-actions>
            <Link
                :href="route('compositions.index')"
                class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium rounded-lg transition flex items-center gap-2"
            >
                <ArrowUturnLeftIcon class="w-4 h-4" />
                Voltar
            </Link>
        </template>

        <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold text-white mb-6">Simular Abertura (2-1)</h1>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Champions input -->
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Campeões no tabuleiro</h2>

                    <!-- Added champions -->
                    <div class="flex flex-wrap gap-2 mb-4 min-h-[52px]">
                        <div
                            v-for="(entry, idx) in selectedChampions"
                            :key="idx"
                            class="relative flex flex-col items-center gap-1"
                        >
                            <div
                                class="relative w-12 h-12 rounded-lg overflow-hidden border-2 cursor-pointer"
                                :class="`cost-${getChampionCost(entry.id)}`"
                                :style="{ borderColor: 'var(--cost-color)' }"
                                :title="getChampionName(entry.id)"
                                @click="toggleStar(idx)"
                            >
                                <img :src="getChampionIcon(entry.id)" :alt="getChampionName(entry.id)" class="w-full h-full object-cover" />
                                <button
                                    @click.stop="removeChampion(idx)"
                                    class="absolute -top-1 -right-1 w-4 h-4 bg-red-600 rounded-full flex items-center justify-center text-white text-[15px] leading-none hover:bg-red-500"
                                >×</button>
                            </div>
                            <span class="text-[10px] font-medium" :class="entry.stars === 2 ? 'text-yellow-400' : 'text-gray-500'">
                                {{ '★'.repeat(entry.stars) }}
                            </span>
                        </div>

                        <button
                            v-if="selectedChampions.length < 9"
                            @click="showChampionPicker = true"
                            class="w-12 h-12 rounded-lg border-2 border-dashed border-gray-700 hover:border-gray-500 flex items-center justify-center text-gray-600 hover:text-gray-400 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>

                    <!-- Active traits from champions -->
                    <div v-if="activeTraits.length > 0">
                        <h3 class="text-xs text-gray-500 uppercase tracking-wider mb-2">Sinergias ativas</h3>
                        <div class="flex flex-wrap gap-1.5">
                            <div
                                v-for="trait in activeTraits"
                                :key="trait.id"
                                class="flex items-center gap-1 px-2 py-0.5 rounded-lg bg-gray-800"
                            >
                                <img v-if="trait.icon" :src="trait.icon" class="w-4 h-4 object-contain" />
                                <span class="text-xs text-gray-300">{{ trait.name }} ({{ trait.count }})</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Components / Emblems input -->
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Componentes & Emblemas</h2>

                    <div class="flex flex-wrap gap-2 mb-4 min-h-[44px]">
                        <div
                            v-for="(itemId, idx) in selectedItems"
                            :key="idx"
                            class="relative w-10 h-10 rounded-md overflow-hidden border border-gray-700 bg-gray-800"
                            :title="getItemName(itemId)"
                        >
                            <img :src="getItemIcon(itemId)" :alt="getItemName(itemId)" class="w-full h-full object-cover" />
                            <button
                                @click="removeItem(idx)"
                                class="absolute -top-1 -right-1 w-4 h-4 bg-red-600 rounded-full flex items-center justify-center text-white text-[15px] leading-none hover:bg-red-500"
                            >×</button>
                        </div>

                        <button
                            @click="showItemPicker = true"
                            class="w-10 h-10 rounded-md border-2 border-dashed border-gray-700 hover:border-gray-500 flex items-center justify-center text-gray-600 hover:text-gray-400 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>

                    <!-- Craftable items preview -->
                    <div v-if="craftableItems.length > 0">
                        <h3 class="text-xs text-gray-500 uppercase tracking-wider mb-2">Itens montáveis</h3>
                        <div class="flex flex-wrap gap-1.5">
                            <div
                                v-for="item in craftableItems"
                                :key="item.id"
                                class="w-8 h-8 rounded-sm overflow-hidden border border-gray-700"
                                :title="item.name"
                            >
                                <img :src="item.icon" :alt="item.name" class="w-full h-full object-cover" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div v-if="results.length > 0">
                <h2 class="text-lg font-semibold text-white mb-4">Melhores composições</h2>
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
                                    {{ rIdx + 1 }}º
                                </span>
                                <h3 class="text-lg font-semibold text-white">{{ result.name }}</h3>
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

                        <!-- Disposition match detail -->
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="(match, mIdx) in result.matches"
                                :key="mIdx"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium"
                                :class="{
                                    'bg-green-900/30 text-green-400': match.percent >= 80,
                                    'bg-yellow-900/30 text-yellow-400': match.percent >= 40 && match.percent < 80,
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
                                            <img :src="getChampionIcon(cid)" class="w-full h-full object-cover" />
                                        </div>
                                    </div>
                                    <span>{{ match.percent }}%</span>
                                </template>
                                <template v-else-if="match.type === 'trait'">
                                    <img v-if="getTraitIcon(match.trait_id)" :src="getTraitIcon(match.trait_id)" class="w-4 h-4 object-contain" />
                                    <span>{{ getTraitName(match.trait_id) }} {{ match.percent }}%</span>
                                </template>
                                <template v-else-if="match.type === 'item'">
                                    <div class="flex items-center gap-0.5">
                                        <div
                                            v-for="iid in (match.item_ids || []).slice(0, 3)"
                                            :key="iid"
                                            class="w-4 h-4 rounded-sm overflow-hidden"
                                        >
                                            <img :src="getItemIcon(iid)" class="w-full h-full object-cover" />
                                        </div>
                                    </div>
                                    <span>{{ match.percent }}%</span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No results -->
            <div v-if="(selectedChampions.length > 0 || selectedItems.length > 0) && results.length === 0" class="text-center py-12">
                <p class="text-gray-500">Nenhuma composição compatível encontrada.</p>
            </div>
        </div>

        <!-- Champion picker modal -->
        <Teleport to="body">
            <div v-if="showChampionPicker" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showChampionPicker = false">
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-6 max-w-2xl w-full">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">Selecionar Campeão</h3>
                        <button @click="showChampionPicker = false" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <input
                        ref="champSearchInput"
                        v-model="champSearchQuery"
                        @keydown.enter.prevent="selectFirstChampion"
                        type="text"
                        placeholder="Buscar campeão..."
                        class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-sm text-gray-200 rounded-lg px-3 py-2 mb-4"
                    />
                    <div class="grid grid-cols-10 gap-2">
                        <div
                            v-for="champ in filteredPickerChampions"
                            :key="champ.id"
                            @click="pickChampion(champ.id)"
                            class="champion-grid-item cursor-pointer"
                            :class="`cost-${champ.cost}`"
                            :title="`${champ.name} ($${champ.cost})`"
                        >
                            <div class="w-full aspect-square bg-gray-800 rounded overflow-hidden" :style="{ borderBottom: `2px solid var(--cost-color)` }">
                                <img
                                    v-if="champ.icon"
                                    :src="champ.icon"
                                    :alt="champ.name"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Item picker modal (components + emblems only) -->
        <Teleport to="body">
            <div v-if="showItemPicker" class="fixed inset-0 bg-black/60 z-50 flex items-start justify-center pt-20 p-4" @click.self="showItemPicker = false">
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-4 max-w-2xl w-full max-h-[70vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-white">Selecionar Componente / Emblema</h3>
                        <button @click="showItemPicker = false" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <input
                        ref="itemSearchInput"
                        v-model="itemSearchQuery"
                        @keydown.enter.prevent="selectFirstItem"
                        type="text"
                        placeholder="Buscar item..."
                        class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-sm text-gray-200 rounded-lg px-3 py-2 mb-4"
                    />

                    <!-- Components -->
                    <template v-if="filteredComponentItems.length > 0">
                        <h4 class="text-xs text-gray-500 uppercase tracking-wider mb-2">Componentes</h4>
                        <div class="grid grid-cols-9 gap-1.5 mb-4">
                            <button
                                v-for="item in filteredComponentItems"
                                :key="item.id"
                                @click="pickItem(item.id)"
                                class="flex flex-col items-center gap-0.5 p-1 rounded-lg hover:bg-gray-800 transition"
                            >
                                <div class="w-10 h-10 rounded-md overflow-hidden border border-gray-700 bg-gray-800">
                                    <img :src="item.icon" :alt="item.name" class="w-full h-full object-cover" loading="lazy" />
                                </div>
                                <span class="text-[9px] text-gray-400 truncate w-full text-center">{{ item.name }}</span>
                            </button>
                        </div>
                    </template>

                    <!-- Emblems -->
                    <template v-if="filteredEmblemItems.length > 0">
                        <h4 class="text-xs text-gray-500 uppercase tracking-wider mb-2">Emblemas</h4>
                        <div class="grid grid-cols-8 gap-1.5">
                            <button
                                v-for="item in filteredEmblemItems"
                                :key="item.id"
                                @click="pickItem(item.id)"
                                class="flex flex-col items-center gap-0.5 p-1 rounded-lg hover:bg-gray-800 transition"
                            >
                                <div class="w-10 h-10 rounded-md overflow-hidden border border-gray-700 bg-gray-800">
                                    <img :src="item.icon" :alt="item.name" class="w-full h-full object-cover" loading="lazy" />
                                </div>
                                <span class="text-[9px] text-gray-400 truncate w-full text-center">{{ item.name }}</span>
                            </button>
                        </div>
                    </template>

                    <p v-if="filteredComponentItems.length === 0 && filteredEmblemItems.length === 0" class="text-xs text-gray-600 text-center py-4">Nenhum item encontrado.</p>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick, watch, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    compositions: { type: Array, default: () => [] },
    tftData: { type: Object, default: () => ({ champions: [], traits: [], items: [] }) },
});

// --- Data maps ---
const championsMap = computed(() => {
    const map = {};
    props.tftData.champions.forEach(c => { map[c.id] = c; });
    return map;
});

const itemsMap = computed(() => {
    const map = {};
    props.tftData.items.forEach(i => { map[i.id] = i; });
    return map;
});

const traitsMap = computed(() => {
    const map = {};
    (props.tftData.traits || []).forEach(t => { map[t.id] = t; });
    return map;
});

// Map trait name → trait id for matching champion traits
const traitNameToId = computed(() => {
    const map = {};
    (props.tftData.traits || []).forEach(t => { map[t.name] = t.id; });
    return map;
});

function getChampionIcon(id) { return championsMap.value[id]?.icon || ''; }
function getChampionName(id) { return championsMap.value[id]?.name || id; }
function getChampionCost(id) { return championsMap.value[id]?.cost || 1; }
function getItemIcon(id) { return itemsMap.value[id]?.icon || ''; }
function getItemName(id) { return itemsMap.value[id]?.name || id; }
function getTraitIcon(id) { return traitsMap.value[id]?.icon || ''; }
function getTraitName(id) { return traitsMap.value[id]?.name || id; }

// --- Selected champions ---
const selectedChampions = ref([]); // [{id, stars}]
const showChampionPicker = ref(false);
const champSearchQuery = ref('');
const champSearchInput = ref(null);

watch(showChampionPicker, (val) => {
    if (val) {
        champSearchQuery.value = '';
        nextTick(() => champSearchInput.value?.focus());
    }
});

function pickChampion(id) {
    addChampion(id);
    champSearchQuery.value = '';
}

const filteredPickerChampions = computed(() => {
    let champs = [...props.tftData.champions].sort((a, b) => a.cost - b.cost || a.name.localeCompare(b.name));
    if (champSearchQuery.value) {
        const q = champSearchQuery.value.toLowerCase();
        champs = champs.filter(c => c.name.toLowerCase().includes(q));
    }
    return champs;
});

function addChampion(id) {
    selectedChampions.value.push({ id, stars: 1 });
}

function selectFirstChampion() {
    const list = filteredPickerChampions.value;
    if (list.length > 0) {
        pickChampion(list[0].id);
    }
}

function removeChampion(idx) {
    selectedChampions.value.splice(idx, 1);
}

function toggleStar(idx) {
    selectedChampions.value[idx].stars = selectedChampions.value[idx].stars === 1 ? 2 : 1;
}

// --- Active traits from selected champions ---
const activeTraits = computed(() => {
    const counts = {};
    selectedChampions.value.forEach(entry => {
        const champ = championsMap.value[entry.id];
        if (!champ) return;
        (champ.traits || []).forEach(t => {
            const traitId = traitNameToId.value[t.name];
            if (traitId) {
                counts[traitId] = (counts[traitId] || 0) + 1;
            }
        });
    });
    return Object.entries(counts).map(([id, count]) => ({
        id,
        name: traitsMap.value[id]?.name || id,
        icon: traitsMap.value[id]?.icon || '',
        count,
    })).sort((a, b) => b.count - a.count);
});

// --- Selected items (components + emblems) ---
const selectedItems = ref([]); // [itemId, itemId, ...]
const showItemPicker = ref(false);
const itemSearchQuery = ref('');
const itemSearchInput = ref(null);

watch(showItemPicker, (val) => {
    if (val) {
        itemSearchQuery.value = '';
        nextTick(() => itemSearchInput.value?.focus());
    }
});

const componentItems = computed(() =>
    props.tftData.items.filter(i => i.category === 'component').sort((a, b) => a.name.localeCompare(b.name))
);

const emblemItems = computed(() =>
    props.tftData.items.filter(i => i.category === 'emblem').sort((a, b) => a.name.localeCompare(b.name))
);

const filteredComponentItems = computed(() => {
    if (!itemSearchQuery.value) return componentItems.value;
    const q = itemSearchQuery.value.toLowerCase();
    return componentItems.value.filter(i => i.name.toLowerCase().includes(q));
});

const filteredEmblemItems = computed(() => {
    if (!itemSearchQuery.value) return emblemItems.value;
    const q = itemSearchQuery.value.toLowerCase();
    return emblemItems.value.filter(i => i.name.toLowerCase().includes(q));
});

function addItem(id) {
    selectedItems.value.push(id);
}

function pickItem(id) {
    addItem(id);
    itemSearchQuery.value = '';
}

function selectFirstItem() {
    const all = [...filteredComponentItems.value, ...filteredEmblemItems.value];
    if (all.length > 0) pickItem(all[0].id);
}

function removeItem(idx) {
    selectedItems.value.splice(idx, 1);
}

// --- ESC to close modals ---
function handleEsc(e) {
    if (e.key !== 'Escape') return;
    if (showChampionPicker.value) showChampionPicker.value = false;
    else if (showItemPicker.value) showItemPicker.value = false;
}

watch([showChampionPicker, showItemPicker], ([champ, item]) => {
    if (champ || item) {
        document.addEventListener('keydown', handleEsc);
    } else {
        document.removeEventListener('keydown', handleEsc);
    }
});

onUnmounted(() => document.removeEventListener('keydown', handleEsc));

// --- Craftable items from selected components ---
const craftableItems = computed(() => {
    // Only base components can be used to craft
    const availableComponents = [...selectedItems.value.filter(id => itemsMap.value[id]?.category === 'component')];
    const combinedItems = props.tftData.items.filter(i => i.category === 'combined' && i.recipe && i.recipe.length === 2);
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
    for (const compId of recipe) {
        const idx = pool.indexOf(compId);
        if (idx === -1) return false;
        pool.splice(idx, 1);
    }
    return true;
}

// --- Simulation / Scoring (reactive) ---
const results = computed(() => {
    if (selectedChampions.value.length === 0 && selectedItems.value.length === 0) return [];

    const scored = props.compositions.map(comp => {
        const { score, matches } = scoreComposition(comp);
        return {
            id: comp.id,
            name: comp.name,
            score: Math.round(score),
            matches,
        };
    });

    scored.sort((a, b) => b.score - a.score);
    return scored.slice(0, 5).filter(r => r.score > 0);
});

function scoreComposition(comp) {
    const dispositions = comp.dispositions || [];
    if (dispositions.length === 0) return { score: 0, matches: [] };

    const matches = dispositions.map(disp => {
        const percent = scoreDisposition(disp);
        return {
            type: disp.type,
            champion_ids: disp.champion_ids,
            trait_id: disp.trait_id,
            item_ids: disp.item_ids,
            percent: Math.round(percent),
        };
    });

    // Weighted average by priority (higher priority = more weight)
    const totalWeight = dispositions.reduce((sum, d) => sum + (d.priority || 1), 0);
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
        case 'champion': return scoreChampionDisp(disp);
        case 'trait': return scoreTraitDisp(disp);
        case 'item': return scoreItemDisp(disp);
        default: return 0;
    }
}

function scoreChampionDisp(disp) {
    const ids = disp.champion_ids || [];
    if (ids.length === 0) return 0;

    // Check if any of the selected champions match any of the disposition champion_ids
    for (const entry of selectedChampions.value) {
        if (ids.includes(entry.id)) {
            // Champion found — check star level
            if (disp.star_level && entry.stars >= disp.star_level) {
                return 100; // Perfect match
            } else if (disp.star_level && entry.stars < disp.star_level) {
                return 70; // Right champion, wrong stars
            }
            return 100; // No star requirement
        }
    }
    return 0;
}

function scoreTraitDisp(disp) {
    if (!disp.trait_id) return 0;
    const requiredCount = disp.trait_count || 1;

    const activeTrait = activeTraits.value.find(t => t.id === disp.trait_id);
    if (!activeTrait) return 0;

    if (activeTrait.count >= requiredCount) {
        return 100; // Exact or exceeds required count
    }

    // Partial: proportion of required count
    return Math.round((activeTrait.count / requiredCount) * 70);
}

function scoreItemDisp(disp) {
    const wantedItemIds = disp.item_ids || [];
    if (wantedItemIds.length === 0) return 0;

    // Available base components from the user's selected items
    const availableComponents = selectedItems.value.filter(id => itemsMap.value[id]?.category === 'component');

    // Check if any of the wanted items can be crafted or is directly available
    for (const wantedId of wantedItemIds) {
        const item = itemsMap.value[wantedId];
        if (!item) continue;

        // If the wanted item is itself a component/emblem and user has it directly
        if (selectedItems.value.includes(wantedId)) {
            return 100;
        }

        // If the wanted item is a combined item, check if we can craft it
        if (item.recipe && item.recipe.length === 2) {
            if (canCraftItem(item.recipe, availableComponents)) {
                return 100;
            }
        }
    }

    // Partial: check if user has at least one component of any recipe
    for (const wantedId of wantedItemIds) {
        const item = itemsMap.value[wantedId];
        if (!item || !item.recipe || item.recipe.length !== 2) continue;

        for (const compId of item.recipe) {
            if (availableComponents.includes(compId)) {
                return 50; // Has one component, needs the other
            }
        }
    }

    return 0;
}
</script>
