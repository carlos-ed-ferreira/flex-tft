<template>
    <div
        class="bg-gray-900 border border-gray-800 rounded-xl p-3"
        :class="horizontal ? '' : ''"
    >
        <h3 class="text-sm font-semibold text-gray-300 mb-3">Sinergias</h3>

        <div v-if="activeTraits.length === 0" class="text-xs text-gray-600 text-center py-4">
            Posicione campeões para ver as sinergias
        </div>

        <div
            :class="horizontal ? 'flex flex-wrap gap-2' : 'space-y-1'"
        >
            <div
                v-for="trait in activeTraits"
                :key="trait.id"
                class="flex items-center gap-2 px-2 py-1.5 rounded-lg transition"
                :class="[
                    trait.isActive ? 'bg-gray-800/80' : 'bg-gray-800/30 opacity-60',
                    horizontal ? '' : '',
                ]"
            >
                <!-- Trait icon -->
                <div
                    class="w-6 h-6 flex-shrink-0 rounded overflow-hidden"
                    :class="tierClass(trait)"
                >
                    <img
                        v-if="trait.icon"
                        :src="trait.icon"
                        :alt="trait.name"
                        class="w-full h-full object-contain"
                        loading="lazy"
                    />
                </div>

                <!-- Trait info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1">
                        <span
                            class="text-xs font-medium truncate"
                            :class="tierTextClass(trait)"
                        >
                            {{ trait.name }}
                        </span>
                    </div>
                    <!-- Breakpoints -->
                    <div class="flex items-center gap-0.5 mt-0.5">
                        <span
                            v-for="(bp, idx) in trait.breakpoints"
                            :key="idx"
                            class="text-[9px] px-1 rounded"
                            :class="[
                                trait.count >= bp.min
                                    ? tierBgClass(bp.style)
                                    : 'bg-gray-700/50 text-gray-500'
                            ]"
                        >
                            {{ bp.min }}
                        </span>
                    </div>
                </div>

                <!-- Count -->
                <span
                    class="text-xs font-bold flex-shrink-0"
                    :class="trait.isActive ? 'text-white' : 'text-gray-600'"
                >
                    {{ trait.count }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    activeTraits: { type: Array, default: () => [] },
    horizontal: { type: Boolean, default: false },
});

function tierClass(trait) {
    if (!trait.isActive) return 'bg-gray-700/50';
    switch (trait.activeTier?.style) {
        case 'kChromatic': return 'bg-gradient-to-br from-pink-500 via-yellow-400 to-cyan-400';
        case 'kGold': return 'bg-yellow-600/30';
        case 'kSilver': return 'bg-gray-400/30';
        case 'kBronze': return 'bg-amber-700/30';
        default: return 'bg-gray-700/50';
    }
}

function tierTextClass(trait) {
    if (!trait.isActive) return 'text-gray-500';
    switch (trait.activeTier?.style) {
        case 'kChromatic': return 'trait-chromatic';
        case 'kGold': return 'trait-gold';
        case 'kSilver': return 'trait-silver';
        case 'kBronze': return 'trait-bronze';
        default: return 'text-gray-400';
    }
}

function tierBgClass(style) {
    switch (style) {
        case 'kChromatic': return 'bg-gradient-to-r from-pink-600 to-cyan-500 text-white';
        case 'kGold': return 'bg-yellow-600/60 text-yellow-200';
        case 'kSilver': return 'bg-gray-400/40 text-gray-200';
        case 'kBronze': return 'bg-amber-700/50 text-amber-200';
        default: return 'bg-gray-600/40 text-gray-300';
    }
}
</script>
