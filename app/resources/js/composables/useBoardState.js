import { ref, computed } from 'vue';

const SUMMON_IDS = {
  bia: '__summon_bia',
  bayin: '__summon_bayin',
  swarmling: '__summon_swarmling',
  meeplord: '__summon_meeplord',
  clone: '__summon_clone',
  mega_meep: '__summon_mega_meep',
};

const SUMMON_TYPES_WITHOUT_ITEMS = new Set(['swarmling', 'clone']);

export function useBoardState(tftData) {
  const ROWS = 4;
  const COLS = 7;

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

  function createSummonCell({
    championId,
    summonType,
    summonName,
    summonIcon = '',
    traitBonuses = [],
  }) {
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
    const normalizedCandidates = candidates.map((c) => normalizeKey(c));

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
    for (const item of tftData.value.items || []) {
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

      for (const itemId of cell.items || []) {
        const item = itemsMap[itemId];
        if (!item || item.category !== 'emblem') continue;
        const grantedTrait =
          item.grantedTrait || item.name?.replace(/\s*emblem\s*$/i, '').trim();
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
        return {
          id: champion.id,
          icon: champion.icon || '',
          name: champion.name,
        };
      }
    }

    const nameCandidates = {
      bia: ['bia'],
      bayin: ['bayin'],
      swarmling: ['swarmling', 'swarmlings'],
      meeplord: ['meeplord', 'meeplords'],
      clone: ['clone', 'leblanc clone'],
      mega_meep: ['mega meep', 'megameep'],
    };

    const candidates = nameCandidates[type] || [];
    for (const champion of champions) {
      const name = normalizeKey(champion?.name);
      if (candidates.some((c) => name.includes(normalizeKey(c)))) {
        return {
          id: champion.id,
          icon: champion.icon || '',
          name: champion.name,
        };
      }
    }

    const fallbackNames = {
      bia: 'Bia',
      bayin: 'Bayin',
      swarmling: 'Swarmling',
      meeplord: 'Meeplord',
      clone: 'Clone',
      mega_meep: 'Mega Meep',
    };
    const fallbackIds = {
      bia: SUMMON_IDS.bia,
      bayin: SUMMON_IDS.bayin,
      swarmling: SUMMON_IDS.swarmling,
      meeplord: SUMMON_IDS.meeplord,
      clone: SUMMON_IDS.clone,
      mega_meep: SUMMON_IDS.mega_meep,
    };
    return {
      id: fallbackIds[type] || `__summon_${type}`,
      icon: '',
      name: fallbackNames[type] || type,
    };
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

    const summonChampions = champions.filter((c) => c.isSummon);
    const knownSummonTypes = new Set(summonChampions.map((c) => c.summonType));

    const summonDataCache = {};
    function getSummonDataCached(type) {
      if (!summonDataCache[type]) summonDataCache[type] = findSummonData(type);
      return summonDataCache[type];
    }

    const summonKeysByType = {};
    for (const type of knownSummonTypes) {
      summonKeysByType[type] = [];
    }

    for (const [key, cell] of Object.entries(boardState.value)) {
      if (!cell?.isSummon) continue;

      if (!summonKeysByType[cell.summonType]) {
        delete boardState.value[key];
        continue;
      }

      const latest = getSummonDataCached(cell.summonType);
      cell.championId = latest.id;
      cell.summonName = latest.name;
      cell.summonIcon = latest.icon;

      summonKeysByType[cell.summonType].push(key);

      if (
        !canCellReceiveItems(cell) &&
        Array.isArray(cell.items) &&
        cell.items.length > 0
      ) {
        cell.items = [];
      }
    }

    boardState.value = { ...boardState.value };
  }

  function loadState(state) {
    boardState.value = state && typeof state === 'object' ? { ...state } : {};
    reconcileSummons();
  }

  function exportState() {
    return { ...boardState.value };
  }

  function placeChampionAuto(championId) {
    const key = findFirstEmptyCellKey();
    if (!key) return false;
    const [row, col] = key.split('-').map(Number);
    placeChampion(row, col, championId);
    return true;
  }

  function toggleStars(row, col) {
    const key = `${row}-${col}`;
    const cell = boardState.value[key];
    if (!cell || cell.isSummon) return;
    cell.starLevel = cell.starLevel === 3 ? 0 : 3;
    boardState.value = { ...boardState.value };
  }

  function placeChampion(row, col, championId) {
    const key = `${row}-${col}`;
    if (isLockedSummon(boardState.value[key])) return;

    boardState.value[key] = {
      championId,
      items: boardState.value[key]?.items || [],
    };
    reconcileSummons();
  }

  function removeChampion(row, col) {
    const key = `${row}-${col}`;
    if (isLockedSummon(boardState.value[key])) return;

    delete boardState.value[key];
    reconcileSummons();
  }

  function moveChampion(fromRow, fromCol, toRow, toCol) {
    const fromKey = `${fromRow}-${fromCol}`;
    const toKey = `${toRow}-${toCol}`;
    const fromCell = boardState.value[fromKey];
    if (!fromCell) return;

    const toCell = boardState.value[toKey];

    if (toCell) {
      boardState.value[toKey] = fromCell;
      boardState.value[fromKey] = toCell;
    } else {
      boardState.value[toKey] = fromCell;
      delete boardState.value[fromKey];
    }
    reconcileSummons();
  }

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

  function removeItem(row, col, itemIndex) {
    const key = `${row}-${col}`;
    const cell = boardState.value[key];
    if (!cell) return;
    if (!canCellReceiveItems(cell)) return;

    cell.items.splice(itemIndex, 1);
    boardState.value = { ...boardState.value };
    reconcileSummons();
  }

  function clearItems(row, col) {
    const key = `${row}-${col}`;
    const cell = boardState.value[key];
    if (!cell) return;
    if (!canCellReceiveItems(cell)) return;

    cell.items = [];
    boardState.value = { ...boardState.value };
    reconcileSummons();
  }

  function getCell(row, col) {
    return boardState.value[`${row}-${col}`] || null;
  }

  const championCount = computed(() => {
    return Object.values(boardState.value).filter((cell) => cell?.championId)
      .length;
  });

  const activeTraits = computed(() => {
    if (!tftData?.value?.champions || !tftData?.value?.traits) return [];

    const traitCounts = buildTraitCounts();

    const traitsMap = {};
    for (const trait of tftData.value.traits) {
      traitsMap[trait.name] = trait;
    }

    const result = [];
    for (const [traitName, count] of Object.entries(traitCounts)) {
      const trait = traitsMap[traitName];
      if (!trait) continue;

      let activeTier = null;
      let nextBreakpoint = null;

      for (const bp of trait.breakpoints) {
        if (count >= bp.min) {
          activeTier = bp;
        }
      }

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
