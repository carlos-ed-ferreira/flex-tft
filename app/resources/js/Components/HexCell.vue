<template>
    <div
        class="hex-cell"
        :class="[
            cell ? `hex-cell-filled cost-${champion?.cost || 1}` : 'hex-cell-empty',
            isDragOver ? 'drag-over' : '',
        ]"
        @click="handleClick"
        @dragover.prevent="onDragOver"
        @dragenter.prevent="onDragOver"
        @dragleave="onDragLeave"
        @drop.prevent="onDrop"
        @dragstart="onDragStart"
        @contextmenu.prevent="onRightClick"
        :draggable="!!cell"
        :style="{ position: 'relative', zIndex: cell ? 10 : (isDragOver ? 10 : 1) }"
    >
        <div class="hex-cell-inner">
            <!-- Empty state -->
            <template v-if="!cell">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                </svg>
            </template>

            <!-- Filled state: champion icon -->
            <template v-else>
                <img
                    v-if="champion?.icon"
                    :src="champion.icon"
                    :alt="champion.name"
                    class="hex-champion-img"
                    draggable="false"
                    loading="lazy"
                />
                <div v-else class="text-xs text-gray-400 text-center px-1">
                    {{ champion?.name || '?' }}
                </div>
            </template>
        </div>

        <!-- Remove button and items OUTSIDE the clipped area -->
        <template v-if="cell">
            <!-- Remove button -->
            <button
                class="hex-remove-btn"
                @click.stop="$emit('remove-champion', { row, col })"
                title="Remover"
            >
                ✕
            </button>

            <!-- Item slots -->
            <div class="hex-items" @click.stop>
                <div
                    v-for="(item, index) in displayItems"
                    :key="index"
                    class="hex-item-slot"
                    :title="item.name"
                    @contextmenu.prevent.stop="$emit('remove-item', { row, col, itemIndex: index })"
                >
                    <img v-if="item.icon" :src="item.icon" :alt="item.name" loading="lazy" />
                </div>
                <!-- Add item button if < 3 items -->
                <button
                    v-if="displayItems.length < 3"
                    class="hex-item-slot flex items-center justify-center text-gray-600 hover:text-gray-400 text-[10px]"
                    @click.stop="$emit('open-item-selector', { row, col })"
                    title="Adicionar item"
                >
                    +
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    row: { type: Number, required: true },
    col: { type: Number, required: true },
    cell: { type: Object, default: null },
    champion: { type: Object, default: null },
    items: { type: Array, default: () => [] },
    selectedChampion: { type: Object, default: null },
});

const emit = defineEmits(['place-champion', 'remove-champion', 'move-champion', 'add-item', 'remove-item', 'open-item-selector']);

const isDragOver = ref(false);

const displayItems = computed(() => props.items || []);

function handleClick() {
    // If a cell is empty and something is being dragged/selected, place it
    if (!props.cell) {
        // This will be handled by the parent via selected champion state
        emit('place-champion', { row: props.row, col: props.col });
    }
}

function onDragStart(event) {
    if (!props.cell) return;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('application/tft-cell', JSON.stringify({
        fromRow: props.row,
        fromCol: props.col,
        type: 'board-champion',
    }));
}

function onDragOver(event) {
    isDragOver.value = true;
    // Use 'copy' for champion panel, 'move' for board-to-board
    const hasChampion = event.dataTransfer.types.includes('application/tft-champion');
    const hasCell = event.dataTransfer.types.includes('application/tft-cell');
    event.dataTransfer.dropEffect = hasCell ? 'move' : 'copy';
}

function onDragLeave() {
    isDragOver.value = false;
}

function onDrop(event) {
    isDragOver.value = false;

    // Check for champion drag from panel
    const championData = event.dataTransfer.getData('application/tft-champion');
    if (championData) {
        const champion = JSON.parse(championData);
        emit('place-champion', { row: props.row, col: props.col, championId: champion.id });
        return;
    }

    // Check for item drag from panel
    const itemData = event.dataTransfer.getData('application/tft-item');
    if (itemData && props.cell) {
        const item = JSON.parse(itemData);
        emit('add-item', { row: props.row, col: props.col, itemId: item.id });
        return;
    }

    // Check for board-to-board champion move
    const cellData = event.dataTransfer.getData('application/tft-cell');
    if (cellData) {
        const { fromRow, fromCol } = JSON.parse(cellData);
        if (fromRow !== props.row || fromCol !== props.col) {
            emit('move-champion', {
                fromRow,
                fromCol,
                toRow: props.row,
                toCol: props.col,
            });
        }
    }
}

function onRightClick() {
    if (props.cell) {
        emit('remove-champion', { row: props.row, col: props.col });
    }
}
</script>
