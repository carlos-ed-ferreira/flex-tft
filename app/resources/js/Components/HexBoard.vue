<template>
    <div class="hex-grid">
        <div
            v-for="row in rows"
            :key="row"
            class="hex-row"
        >
            <HexCell
                v-for="col in cols"
                :key="`${row - 1}-${col - 1}`"
                :row="row - 1"
                :col="col - 1"
                :cell="getCell(row - 1, col - 1)"
                :champion="getCellChampion(row - 1, col - 1)"
                :items="getCellItems(row - 1, col - 1)"
                :selectedChampion="selectedChampion"
                @place-champion="$emit('place-champion', $event)"
                @remove-champion="$emit('remove-champion', $event)"
                @move-champion="$emit('move-champion', $event)"
                @add-item="$emit('add-item', $event)"
                @remove-item="$emit('remove-item', $event)"
                @open-item-selector="$emit('open-item-selector', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import HexCell from '@/Components/HexCell.vue';

const props = defineProps({
    boardState: { type: Object, required: true },
    tftData: { type: Object, required: true },
    rows: { type: Number, default: 4 },
    cols: { type: Number, default: 7 },
    selectedChampion: { type: Object, default: null },
});

defineEmits(['place-champion', 'remove-champion', 'move-champion', 'add-item', 'remove-item', 'open-item-selector']);

const championsMap = computed(() => {
    const map = {};
    for (const champ of (props.tftData?.champions || [])) {
        map[champ.id] = champ;
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

function getCell(row, col) {
    return props.boardState[`${row}-${col}`] || null;
}

function getCellChampion(row, col) {
    const cell = getCell(row, col);
    if (!cell?.championId) return null;
    return championsMap.value[cell.championId] || null;
}

function getCellItems(row, col) {
    const cell = getCell(row, col);
    if (!cell?.items?.length) return [];
    return cell.items.map(id => itemsMap.value[id] || { id, name: id, icon: '' });
}
</script>
