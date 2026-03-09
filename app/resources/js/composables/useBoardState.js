import { ref, computed } from 'vue';

const SUMMON_IDS = {
    tibbers: '__summon_tibbers',
    soldier: '__summon_soldier',
    iceTower: '__summon_ice_tower',
};

const SUMMON_TYPES_WITHOUT_ITEMS = new Set(['soldier', 'ice_tower']);

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

    function normalizeKey(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function isLockedSummon(cell) {
        return !!cell?.isSummon && cell?.locked === true;
    }

    function canCellReceiveItems(cell) {
        if (!cell) return false;
        if (!cell.isSummon) return true;
        return !SUMMON_TYPES_WITHOUT_ITEMS.has(cell.summonType);
    }

    function createSummonCell({ championId, summonType, summonName, summonIcon = '', traitBonuses = [] }) {
        return {
            championId,
            items: [],
            isSummon: true,
            summonType,
            summonName,
            summonIcon,
            traitBonuses,
            locked: true,
        };
    }

    function findChampionIdByNameCandidates(candidates) {
        const champions = tftData?.value?.champions || [];
        const normalizedCandidates = candidates.map(c => normalizeKey(c));

        for (const champion of champions) {
            const championName = normalizeKey(champion?.name);
            if (normalizedCandidates.includes(championName)) {
                return champion.id;
            }
        }

        return null;
    }

    function findTraitByNameFragment(fragment) {
        const traits = tftData?.value?.traits || [];
        const fragmentKey = normalizeKey(fragment);

        for (const trait of traits) {
            const name = normalizeKey(trait?.name);
            if (name.includes(fragmentKey)) {
                return trait;
            }
        }

        return null;
    }

    function buildTraitCounts() {
        if (!tftData?.value?.champions) return {};

        const championsMap = {};
        for (const champ of tftData.value.champions) {
            championsMap[champ.id] = champ;
        }

        const itemsMap = {};
        for (const item of (tftData.value.items || [])) {
            itemsMap[item.id] = item;
        }

        const traitCounts = {};
        const placedChampionIds = new Set();

        for (const cell of Object.values(boardState.value)) {
            if (!cell?.championId) continue;

            if (cell.isSummon) {
                if (Array.isArray(cell.traitBonuses)) {
                    for (const bonus of cell.traitBonuses) {
                        if (!bonus) continue;
                        if (!traitCounts[bonus]) traitCounts[bonus] = 0;
                        traitCounts[bonus]++;
                    }
                }
                continue;
            }

            if (placedChampionIds.has(cell.championId)) continue;
            placedChampionIds.add(cell.championId);

            const champ = championsMap[cell.championId];
            if (champ && Array.isArray(champ.traits)) {
                for (const trait of champ.traits) {
                    const traitName = trait?.name;
                    if (!traitName) continue;
                    if (!traitCounts[traitName]) traitCounts[traitName] = 0;
                    traitCounts[traitName]++;
                }
            }

            // Count emblem items: each emblem grants +1 to the associated trait
            for (const itemId of (cell.items || [])) {
                const item = itemsMap[itemId];
                if (!item || item.category !== 'emblem') continue;
                const grantedTrait = item.grantedTrait || item.name?.replace(/\s*emblem\s*$/i, '').trim();
                if (!grantedTrait) continue;
                if (!traitCounts[grantedTrait]) traitCounts[grantedTrait] = 0;
                traitCounts[grantedTrait]++;
            }
        }

        return traitCounts;
    }

    function findSummonData(type) {
        const champions = tftData?.value?.champions || [];

        for (const champion of champions) {
            if (champion.isSummon && champion.summonType === type) {
                return { id: champion.id, icon: champion.icon || '', name: champion.name };
            }
        }

        const nameCandidates = {
            tibbers: ['tibbers'],
            soldier: ['sand soldier', 'soldier', 'soldado'],
            ice_tower: ['frozen pillar', 'ice tower', 'freljord tower', 'torre de gelo'],
        };

        const candidates = (nameCandidates[type] || []);
        for (const champion of champions) {
            const name = normalizeKey(champion?.name);
            if (candidates.some(c => name.includes(normalizeKey(c)))) {
                return { id: champion.id, icon: champion.icon || '', name: champion.name };
            }
        }

        if (type === 'ice_tower') {
            const freljordTrait = findTraitByNameFragment('freljord');
            return { id: SUMMON_IDS.iceTower, icon: freljordTrait?.icon || '', name: 'Torre de Gelo' };
        }

        const fallbackNames = { tibbers: 'Tibbers', soldier: 'Soldado', ice_tower: 'Torre de Gelo' };
        const fallbackIds = { tibbers: SUMMON_IDS.tibbers, soldier: SUMMON_IDS.soldier, ice_tower: SUMMON_IDS.iceTower };
        return { id: fallbackIds[type] || `__summon_${type}`, icon: '', name: fallbackNames[type] || type };
    }

    function findFirstEmptyCellKey() {
        for (let row = 0; row < ROWS; row++) {
            for (let col = 0; col < COLS; col++) {
                const key = `${row}-${col}`;
                if (!boardState.value[key]) return key;
            }
        }
        return null;
    }

    function reconcileSummons() {
        const champions = tftData?.value?.champions || [];
        if (!champions.length) return;

        const annieId = findChampionIdByNameCandidates(['Annie']);
        const azirId = findChampionIdByNameCandidates(['Azir']);

        const tibbersData = findSummonData('tibbers');
        const soldierData = findSummonData('soldier');
        const towerData = findSummonData('ice_tower');

        const arcanistTrait = findTraitByNameFragment('arcan');
        const freljordTrait = findTraitByNameFragment('freljord');

        const nonSummonCells = Object.values(boardState.value).filter(cell => !cell?.isSummon);
        const annieCount = annieId
            ? nonSummonCells.filter(cell => cell?.championId === annieId).length
            : 0;
        const azirCount = azirId
            ? nonSummonCells.filter(cell => cell?.championId === azirId).length
            : 0;

        const traitCounts = buildTraitCounts();
        const freljordCurrentCount = freljordTrait ? (traitCounts[freljordTrait.name] || 0) : 0;

        let freljordTowersDesired = 0;
        if (freljordTrait && Array.isArray(freljordTrait.breakpoints)) {
            for (const bp of freljordTrait.breakpoints) {
                if (freljordCurrentCount >= Number(bp.min || 0)) {
                    freljordTowersDesired++;
                }
            }
        }

        const desiredCounts = {
            tibbers: annieCount,
            soldier: azirCount * 2,
            ice_tower: freljordTowersDesired,
        };

        const summonKeysByType = {
            tibbers: [],
            soldier: [],
            ice_tower: [],
        };

        const summonDataCache = {};
        function getSummonDataCached(type) {
            if (!summonDataCache[type]) summonDataCache[type] = findSummonData(type);
            return summonDataCache[type];
        }

        for (const [key, cell] of Object.entries(boardState.value)) {
            if (!cell?.isSummon) continue;

            if (!summonKeysByType[cell.summonType]) {
                delete boardState.value[key];
                continue;
            }

            // Refresh summon metadata (icon, championId, name) from latest data
            const latest = getSummonDataCached(cell.summonType);
            cell.championId = latest.id;
            cell.summonName = latest.name;
            cell.summonIcon = latest.icon;

            summonKeysByType[cell.summonType].push(key);

            if (!canCellReceiveItems(cell) && Array.isArray(cell.items) && cell.items.length > 0) {
                cell.items = [];
            }
        }

        while (summonKeysByType.tibbers.length > desiredCounts.tibbers) {
            const key = summonKeysByType.tibbers.pop();
            delete boardState.value[key];
        }
        while (summonKeysByType.soldier.length > desiredCounts.soldier) {
            const key = summonKeysByType.soldier.pop();
            delete boardState.value[key];
        }
        while (summonKeysByType.ice_tower.length > desiredCounts.ice_tower) {
            const key = summonKeysByType.ice_tower.pop();
            delete boardState.value[key];
        }

        while (summonKeysByType.tibbers.length < desiredCounts.tibbers) {
            const emptyKey = findFirstEmptyCellKey();
            if (!emptyKey) break;

            boardState.value[emptyKey] = createSummonCell({
                championId: tibbersData.id,
                summonType: 'tibbers',
                summonName: tibbersData.name,
                summonIcon: tibbersData.icon,
                traitBonuses: arcanistTrait ? [arcanistTrait.name] : [],
            });
            summonKeysByType.tibbers.push(emptyKey);
        }

        while (summonKeysByType.soldier.length < desiredCounts.soldier) {
            const emptyKey = findFirstEmptyCellKey();
            if (!emptyKey) break;

            boardState.value[emptyKey] = createSummonCell({
                championId: soldierData.id,
                summonType: 'soldier',
                summonName: soldierData.name,
                summonIcon: soldierData.icon,
            });
            summonKeysByType.soldier.push(emptyKey);
        }

        while (summonKeysByType.ice_tower.length < desiredCounts.ice_tower) {
            const emptyKey = findFirstEmptyCellKey();
            if (!emptyKey) break;

            boardState.value[emptyKey] = createSummonCell({
                championId: towerData.id,
                summonType: 'ice_tower',
                summonName: towerData.name,
                summonIcon: towerData.icon,
            });
            summonKeysByType.ice_tower.push(emptyKey);
        }

        boardState.value = { ...boardState.value };
    }

    /**
     * Load a board state (from saved level data).
     */
    function loadState(state) {
        boardState.value = state && typeof state === 'object' ? { ...state } : {};
        reconcileSummons();
    }

    /**
     * Export current board state as plain object.
     */
    function exportState() {
        return { ...boardState.value };
    }

    /**
     * Auto-place a champion in the first available empty cell.
     */
    function placeChampionAuto(championId) {
        const key = findFirstEmptyCellKey();
        if (!key) return false;
        const [row, col] = key.split('-').map(Number);
        placeChampion(row, col, championId);
        return true;
    }

    /**
     * Toggle star level (0 ↔ 3) for a champion on the board.
     */
    function toggleStars(row, col) {
        const key = `${row}-${col}`;
        const cell = boardState.value[key];
        if (!cell || cell.isSummon) return;
        cell.starLevel = cell.starLevel === 3 ? 0 : 3;
        boardState.value = { ...boardState.value };
    }

    /**
     * Place a champion on the board at a given position.
     */
    function placeChampion(row, col, championId) {
        const key = `${row}-${col}`;
        if (isLockedSummon(boardState.value[key])) return;

        boardState.value[key] = {
            championId,
            items: boardState.value[key]?.items || [],
        };
        reconcileSummons();
    }

    /**
     * Remove a champion from a position.
     */
    function removeChampion(row, col) {
        const key = `${row}-${col}`;
        if (isLockedSummon(boardState.value[key])) return;

        delete boardState.value[key];
        reconcileSummons();
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
        reconcileSummons();
    }

    /**
     * Add an item to a champion's item slots (max 3).
     */
    function addItem(row, col, itemId) {
        const key = `${row}-${col}`;
        const cell = boardState.value[key];
        if (!cell || !cell.championId) return;
        if (!canCellReceiveItems(cell)) return;
        if (cell.items.length >= 3) return;

        cell.items.push(itemId);
        boardState.value = { ...boardState.value };
        reconcileSummons();
    }

    /**
     * Remove an item from a champion's item slot.
     */
    function removeItem(row, col, itemIndex) {
        const key = `${row}-${col}`;
        const cell = boardState.value[key];
        if (!cell) return;
        if (!canCellReceiveItems(cell)) return;

        cell.items.splice(itemIndex, 1);
        boardState.value = { ...boardState.value };
        reconcileSummons();
    }

    /**
     * Clear all items from a champion's item slots.
     */
    function clearItems(row, col) {
        const key = `${row}-${col}`;
        const cell = boardState.value[key];
        if (!cell) return;
        if (!canCellReceiveItems(cell)) return;

        cell.items = [];
        boardState.value = { ...boardState.value };
        reconcileSummons();
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

        const traitCounts = buildTraitCounts();

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
        placeChampionAuto,
        removeChampion,
        moveChampion,
        toggleStars,
        addItem,
        removeItem,
        clearItems,
        getCell,
        championCount,
        activeTraits,
    };
}
