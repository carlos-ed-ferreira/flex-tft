import { ref, computed } from 'vue';

/**
 * Composable for managing the board state of a composition level.
 * Board is 4 rows × 7 columns of hexagonal cells.
 * State is a reactive object mapping "row-col" → { championId, items[] }
 */
export function useBoardState(tftData) {
    const ROWS = 4;
    const COLS = 7;

    // Current board state: { "0-0": { championId: "...", items: [] }, ... }
    const boardState = ref({});

    /**
     * Load a board state (from saved level data).
     */
    function loadState(state) {
        boardState.value = state && typeof state === 'object' ? { ...state } : {};
    }

    /**
     * Export current board state as plain object.
     */
    function exportState() {
        return { ...boardState.value };
    }

    /**
     * Place a champion on the board at a given position.
     */
    function placeChampion(row, col, championId) {
        const key = `${row}-${col}`;
        boardState.value[key] = {
            championId,
            items: boardState.value[key]?.items || [],
        };
        // Trigger reactivity
        boardState.value = { ...boardState.value };
    }

    /**
     * Remove a champion from a position.
     */
    function removeChampion(row, col) {
        const key = `${row}-${col}`;
        delete boardState.value[key];
        boardState.value = { ...boardState.value };
    }

    /**
     * Move champion from one hex to another.
     */
    function moveChampion(fromRow, fromCol, toRow, toCol) {
        const fromKey = `${fromRow}-${fromCol}`;
        const toKey = `${toRow}-${toCol}`;
        const fromCell = boardState.value[fromKey];
        if (!fromCell) return;

        const toCell = boardState.value[toKey];

        if (toCell) {
            // Swap champions
            boardState.value[toKey] = fromCell;
            boardState.value[fromKey] = toCell;
        } else {
            // Move to empty cell
            boardState.value[toKey] = fromCell;
            delete boardState.value[fromKey];
        }
        boardState.value = { ...boardState.value };
    }

    /**
     * Add an item to a champion's item slots (max 3).
     */
    function addItem(row, col, itemId) {
        const key = `${row}-${col}`;
        const cell = boardState.value[key];
        if (!cell || !cell.championId) return;
        if (cell.items.length >= 3) return;

        cell.items.push(itemId);
        boardState.value = { ...boardState.value };
    }

    /**
     * Remove an item from a champion's item slot.
     */
    function removeItem(row, col, itemIndex) {
        const key = `${row}-${col}`;
        const cell = boardState.value[key];
        if (!cell) return;

        cell.items.splice(itemIndex, 1);
        boardState.value = { ...boardState.value };
    }

    /**
     * Clear all items from a champion's item slots.
     */
    function clearItems(row, col) {
        const key = `${row}-${col}`;
        const cell = boardState.value[key];
        if (!cell) return;

        cell.items = [];
        boardState.value = { ...boardState.value };
    }

    /**
     * Get the cell data at a specific position.
     */
    function getCell(row, col) {
        return boardState.value[`${row}-${col}`] || null;
    }

    /**
     * Get count of placed champions.
     */
    const championCount = computed(() => {
        return Object.values(boardState.value).filter(cell => cell?.championId).length;
    });

    /**
     * Compute active traits/synergies based on placed champions.
     */
    const activeTraits = computed(() => {
        if (!tftData?.value?.champions || !tftData?.value?.traits) return [];

        const championsMap = {};
        for (const champ of tftData.value.champions) {
            championsMap[champ.id] = champ;
        }

        // Count traits from all placed champions
        const traitCounts = {};
        const placedChampionIds = new Set();

        for (const cell of Object.values(boardState.value)) {
            if (!cell?.championId) continue;
            // Don't double-count the same champion
            if (placedChampionIds.has(cell.championId)) continue;
            placedChampionIds.add(cell.championId);

            const champ = championsMap[cell.championId];
            if (!champ) continue;

            for (const trait of champ.traits) {
                const traitName = trait.name;
                if (!traitCounts[traitName]) {
                    traitCounts[traitName] = 0;
                }
                traitCounts[traitName]++;
            }
        }

        // Check also for emblem items that grant traits
        // (Emblem items contain the trait name in their ID, e.g. TFT13_Item_BilgewaterEmblemItem)
        // This is a simplified check

        // Match trait counts against trait breakpoints
        const traitsMap = {};
        for (const trait of tftData.value.traits) {
            traitsMap[trait.name] = trait;
        }

        const result = [];
        for (const [traitName, count] of Object.entries(traitCounts)) {
            const trait = traitsMap[traitName];
            if (!trait) continue;

            // Find the active tier
            let activeTier = null;
            let nextBreakpoint = null;

            for (const bp of trait.breakpoints) {
                if (count >= bp.min) {
                    activeTier = bp;
                }
            }

            // Find next breakpoint
            for (const bp of trait.breakpoints) {
                if (count < bp.min) {
                    nextBreakpoint = bp;
                    break;
                }
            }

            result.push({
                id: trait.id,
                name: trait.name,
                icon: trait.icon,
                count,
                activeTier,
                nextBreakpoint,
                breakpoints: trait.breakpoints,
                isActive: activeTier !== null,
            });
        }

        // Sort: active traits first (by tier level desc), then inactive, then alphabetically
        const styleOrder = { kChromatic: 4, kGold: 3, kSilver: 2, kBronze: 1 };
        result.sort((a, b) => {
            if (a.isActive && !b.isActive) return -1;
            if (!a.isActive && b.isActive) return 1;
            if (a.isActive && b.isActive) {
                const aStyle = styleOrder[a.activeTier?.style] || 0;
                const bStyle = styleOrder[b.activeTier?.style] || 0;
                if (aStyle !== bStyle) return bStyle - aStyle;
            }
            return a.name.localeCompare(b.name);
        });

        return result;
    });

    return {
        ROWS,
        COLS,
        boardState,
        loadState,
        exportState,
        placeChampion,
        removeChampion,
        moveChampion,
        addItem,
        removeItem,
            clearItems,
        getCell,
        championCount,
        activeTraits,
    };
}
