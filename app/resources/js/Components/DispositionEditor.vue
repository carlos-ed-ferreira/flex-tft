<template>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-300">Abertura Ideal</h3>
            <div class="flex gap-1">
                <button
                    @click="addDisposition('champion')"
                    class="px-2 py-1 text-[10px] font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 rounded transition"
                >
                    + Campeão
                </button>
                <button
                    @click="addDisposition('trait')"
                    class="px-2 py-1 text-[10px] font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 rounded transition"
                >
                    + Sinergia
                </button>
                <button
                    @click="addDisposition('item')"
                    class="px-2 py-1 text-[10px] font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 rounded transition"
                >
                    + Item
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="dispositions.length === 0" class="text-center py-6 text-xs text-gray-600">
            Adicione condições para identificar quando jogar esta composição
        </div>

        <!-- Disposition list -->
        <div class="space-y-2">
            <div
                v-for="(disp, index) in dispositions"
                :key="index"
                class="flex items-center gap-2 bg-gray-800/60 rounded-lg px-3 py-2"
            >
                <!-- Type badge -->
                <span
                    class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded flex-shrink-0"
                    :class="typeBadgeClass(disp.type)"
                >
                    {{ typeLabel(disp.type) }}
                </span>

                <!-- Champion type -->
                <template v-if="disp.type === 'champion'">
                    <div class="flex items-center gap-1 flex-wrap flex-1 min-w-0">
                        <!-- Already selected champions -->
                        <div
                            v-for="(champId, cIdx) in (disp.champion_ids || [])"
                            :key="champId"
                            class="relative group/champ"
                        >
                            <div
                                class="w-7 h-7 rounded bg-gray-700 overflow-hidden"
                                :class="getChampion(champId) ? `cost-${getChampion(champId).cost}` : ''"
                                :style="getChampion(champId) ? { borderBottom: '2px solid var(--cost-color)' } : {}"
                                :title="getChampion(champId)?.name || champId"
                            >
                                <img
                                    v-if="getChampion(champId)?.icon"
                                    :src="getChampion(champId).icon"
                                    :alt="getChampion(champId).name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <button
                                @click="removeChampionFromDisposition(index, cIdx)"
                                class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-red-600 text-white rounded-full text-[8px] flex items-center justify-center opacity-0 group-hover/champ:opacity-100 transition"
                            >×</button>
                        </div>

                        <!-- Add champion button -->
                        <button
                            @click="openPicker(index, 'champion')"
                            class="w-7 h-7 rounded border border-dashed border-gray-600 hover:border-gray-500 flex items-center justify-center text-gray-500 hover:text-gray-400 transition text-xs"
                            title="Adicionar opção de campeão"
                        >+</button>

                        <!-- Star level -->
                        <button
                            @click="toggleStarLevel(index)"
                            class="ms-2 flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-gray-700/60 hover:bg-gray-700 transition flex-shrink-0"
                            :title="`${disp.star_level || 1} estrela(s)`"
                        >
                            <span class="text-xs text-yellow-400">★</span>
                            <span v-if="(disp.star_level || 1) >= 2" class="text-xs text-yellow-400">★</span>
                        </button>
                    </div>
                </template>

                <!-- Trait type -->
                <template v-if="disp.type === 'trait'">
                    <button
                        @click="openPicker(index, 'trait')"
                        class="flex items-center gap-1.5 bg-gray-700/60 hover:bg-gray-700 rounded px-2 py-1 transition min-w-0"
                    >
                        <template v-if="disp.trait_id && getTrait(disp.trait_id)">
                            <img
                                :src="getTrait(disp.trait_id).icon"
                                :alt="getTrait(disp.trait_id).name"
                                class="w-5 h-5 rounded object-contain flex-shrink-0"
                            />
                            <span class="text-xs text-gray-200 truncate">{{ getTrait(disp.trait_id).name }}</span>
                        </template>
                        <span v-else class="text-xs text-gray-500">Selecionar...</span>
                    </button>

                    <!-- Trait count -->
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button
                            @click="changeTraitCount(index, -1)"
                            class="w-5 h-5 flex items-center justify-center bg-gray-700 hover:bg-gray-600 text-gray-300 rounded text-xs transition"
                            :disabled="(disp.trait_count || 1) <= 1"
                        >−</button>
                        <span class="text-xs text-gray-200 w-4 text-center">{{ disp.trait_count || 1 }}</span>
                        <button
                            @click="changeTraitCount(index, 1)"
                            class="w-5 h-5 flex items-center justify-center bg-gray-700 hover:bg-gray-600 text-gray-300 rounded text-xs transition"
                        >+</button>
                    </div>
                </template>

                <!-- Item type -->
                <template v-if="disp.type === 'item'">
                    <div class="flex items-center gap-1 flex-wrap flex-1 min-w-0">
                        <!-- Already selected items -->
                        <div
                            v-for="(itemId, iIdx) in (disp.item_ids || [])"
                            :key="itemId"
                            class="relative group/item"
                        >
                            <div
                                class="w-7 h-7 rounded bg-gray-700 overflow-hidden"
                                :title="getItem(itemId)?.name || itemId"
                            >
                                <img
                                    v-if="getItem(itemId)?.icon"
                                    :src="getItem(itemId).icon"
                                    :alt="getItem(itemId).name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <button
                                @click="removeItemFromDisposition(index, iIdx)"
                                class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-red-600 text-white rounded-full text-[8px] flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition"
                            >×</button>
                        </div>

                        <!-- Add item button -->
                        <button
                            @click="openPicker(index, 'item')"
                            class="w-7 h-7 rounded border border-dashed border-gray-600 hover:border-gray-500 flex items-center justify-center text-gray-500 hover:text-gray-400 transition text-xs"
                            title="Adicionar opção de item"
                        >+</button>
                    </div>
                </template>

                <!-- Remove button -->
                <button
                    @click="removeDisposition(index)"
                    class="ml-auto flex-shrink-0 w-5 h-5 flex items-center justify-center text-gray-600 hover:text-red-400 transition"
                    title="Remover"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Picker modal -->
        <Teleport to="body">
            <div
                v-if="pickerOpen"
                class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
                @click.self="closePicker"
            >
                <div class="bg-gray-900 border border-gray-700 rounded-xl p-5 max-w-lg w-full max-h-[70vh] flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-white">
                            {{ pickerType === 'champion' ? 'Selecionar Campeão' : pickerType === 'trait' ? 'Selecionar Sinergia' : 'Selecionar Item' }}
                        </h3>
                        <button @click="closePicker" class="text-gray-400 hover:text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <input
                        ref="pickerInput"
                        v-model="pickerSearch"
                        @keydown.enter.prevent="selectFirstPickerResult"
                        type="text"
                        placeholder="Buscar..."
                        class="w-full bg-gray-800 border border-gray-700 focus:border-blue-500 focus:ring-0 text-xs text-gray-200 rounded-lg px-3 py-2 mb-3"
                    />

                    <div class="overflow-y-auto flex-1">
                        <!-- Champions -->
                        <div v-if="pickerType === 'champion'" class="grid grid-cols-8 gap-1.5">
                            <div
                                v-for="champ in pickerFilteredChampions"
                                :key="champ.id"
                                @click="selectFromPicker(champ)"
                                class="cursor-pointer"
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

                        <!-- Traits -->
                        <div v-if="pickerType === 'trait'" class="space-y-1">
                            <div
                                v-for="trait in pickerFilteredTraits"
                                :key="trait.id"
                                @click="selectFromPicker(trait)"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer hover:bg-gray-800 transition"
                            >
                                <img
                                    v-if="trait.icon"
                                    :src="trait.icon"
                                    :alt="trait.name"
                                    class="w-6 h-6 object-contain"
                                />
                                <span class="text-sm text-gray-200">{{ trait.name }}</span>
                            </div>
                        </div>

                        <!-- Items -->
                        <div v-if="pickerType === 'item'" class="grid grid-cols-8 gap-1.5">
                            <div
                                v-for="item in pickerFilteredItems"
                                :key="item.id"
                                @click="selectFromPicker(item)"
                                class="w-9 h-9 cursor-pointer rounded overflow-hidden bg-gray-800 hover:ring-1 hover:ring-blue-500 transition"
                                :title="item.name"
                            >
                                <img
                                    v-if="item.icon"
                                    :src="item.icon"
                                    :alt="item.name"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, watch, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    tftData: { type: Object, required: true },
});

const emit = defineEmits(['update:modelValue']);

const dispositions = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

// Picker state
const pickerOpen = ref(false);
const pickerType = ref('champion'); // 'champion' | 'trait' | 'item'
const pickerSearch = ref('');
const pickerTargetIndex = ref(null);
const pickerInput = ref(null);

// Data maps
const championsMap = computed(() => {
    const map = {};
    for (const champ of (props.tftData?.champions || [])) {
        map[champ.id] = champ;
    }
    return map;
});

const traitsMap = computed(() => {
    const map = {};
    for (const trait of (props.tftData?.traits || [])) {
        map[trait.id] = trait;
    }
    return map;
});

const itemsMap = computed(() => {
    const map = {};
    for (const item of (props.tftData?.items || [])) {
        map[item.id] = item;
    }
    return map;
});

function getChampion(id) { return championsMap.value[id] || null; }
function getTrait(id) { return traitsMap.value[id] || null; }
function getItem(id) { return itemsMap.value[id] || null; }

function typeLabel(type) {
    return { champion: 'Campeão', trait: 'Sinergia', item: 'Itens' }[type] || type;
}

function typeBadgeClass(type) {
    return {
        champion: 'bg-purple-900/60 text-purple-300',
        trait: 'bg-amber-900/60 text-amber-300',
        item: 'bg-blue-900/60 text-blue-300',
    }[type] || 'bg-gray-700 text-gray-400';
}

// --- Disposition CRUD ---

function addDisposition(type) {
    const newDisp = { type, priority: dispositions.value.length };

    if (type === 'champion') {
        newDisp.champion_ids = [];
        newDisp.star_level = 1;
    } else if (type === 'trait') {
        newDisp.trait_id = null;
        newDisp.trait_count = 1;
    } else if (type === 'item') {
        newDisp.item_ids = [];
    }

    dispositions.value = [...dispositions.value, newDisp];
}

function removeDisposition(index) {
    const arr = [...dispositions.value];
    arr.splice(index, 1);
    // Re-index priorities
    arr.forEach((d, i) => d.priority = i);
    dispositions.value = arr;
}

function toggleStarLevel(index) {
    const arr = [...dispositions.value];
    const current = arr[index].star_level || 1;
    arr[index] = {
        ...arr[index],
        star_level: current === 1 ? 2 : 1,
    };
    dispositions.value = arr;
}

function changeTraitCount(index, delta) {
    const arr = [...dispositions.value];
    const current = arr[index].trait_count || 1;
    const newVal = Math.max(1, current + delta);
    arr[index] = { ...arr[index], trait_count: newVal };
    dispositions.value = arr;
}

function removeChampionFromDisposition(dispIndex, champIndex) {
    const arr = [...dispositions.value];
    const champs = [...(arr[dispIndex].champion_ids || [])];
    champs.splice(champIndex, 1);
    arr[dispIndex] = { ...arr[dispIndex], champion_ids: champs };
    dispositions.value = arr;
}

function removeItemFromDisposition(dispIndex, itemIndex) {
    const arr = [...dispositions.value];
    const items = [...(arr[dispIndex].item_ids || [])];
    items.splice(itemIndex, 1);
    arr[dispIndex] = { ...arr[dispIndex], item_ids: items };
    dispositions.value = arr;
}

// --- Picker ---

function openPicker(index, type) {
    pickerTargetIndex.value = index;
    pickerType.value = type;
    pickerSearch.value = '';
    pickerOpen.value = true;
    nextTick(() => {
        pickerInput.value?.focus();
    });
}

function closePicker() {
    pickerOpen.value = false;
    pickerTargetIndex.value = null;
}

const pickerFilteredChampions = computed(() => {
    const s = pickerSearch.value.toLowerCase();
    return (props.tftData?.champions || []).filter(c => {
        if (!s) return true;
        return c.name.toLowerCase().includes(s) || c.traits?.some(t => t.name?.toLowerCase().includes(s));
    });
});

const pickerFilteredTraits = computed(() => {
    const s = pickerSearch.value.toLowerCase();
    return (props.tftData?.traits || []).filter(t => {
        if (!s) return true;
        return t.name.toLowerCase().includes(s);
    });
});

const pickerFilteredItems = computed(() => {
    const s = pickerSearch.value.toLowerCase();
    return (props.tftData?.items || []).filter(i => {
        if (!s) return true;
        return i.name.toLowerCase().includes(s);
    });
});

function selectFromPicker(entity) {
    const idx = pickerTargetIndex.value;
    if (idx === null) return;

    const arr = [...dispositions.value];
    const disp = { ...arr[idx] };

    if (pickerType.value === 'champion') {
        const champs = [...(disp.champion_ids || [])];
        if (!champs.includes(entity.id)) {
            champs.push(entity.id);
        }
        disp.champion_ids = champs;
    } else if (pickerType.value === 'trait') {
        disp.trait_id = entity.id;
    } else if (pickerType.value === 'item') {
        const items = [...(disp.item_ids || [])];
        if (!items.includes(entity.id)) {
            items.push(entity.id);
        }
        disp.item_ids = items;
    }

    arr[idx] = disp;
    dispositions.value = arr;

    // For champions and items, keep picker open so user can add multiple options
    if (pickerType.value === 'trait') {
        closePicker();
    }
}

function selectFirstPickerResult() {
    let list;
    if (pickerType.value === 'champion') list = pickerFilteredChampions.value;
    else if (pickerType.value === 'trait') list = pickerFilteredTraits.value;
    else list = pickerFilteredItems.value;

    if (list.length > 0) {
        selectFromPicker(list[0]);
    }
}

// ESC to close picker
function handleEsc(e) {
    if (e.key === 'Escape' && pickerOpen.value) {
        closePicker();
    }
}

watch(pickerOpen, (open) => {
    if (open) {
        document.addEventListener('keydown', handleEsc);
    } else {
        document.removeEventListener('keydown', handleEsc);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEsc);
});
</script>
