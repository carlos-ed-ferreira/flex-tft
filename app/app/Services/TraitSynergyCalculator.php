<?php

namespace App\Services;

class TraitSynergyCalculator
{
    private const STYLE_MAP = [
        'kBronze' => 1,
        'kSilver' => 2,
        'kGold' => 3,
        'kChromatic' => 4,
        'kUnique' => 4,
    ];

    public function calculate(mixed $boardState, array $champions, array $items, array $traits): array
    {
        $state = $this->normalizeBoardState($boardState);

        if (empty($state)) {
            return [];
        }

        $traitCounts = $this->countTraits($state, $champions, $items);

        return $this->activeTraits($traitCounts, $traits);
    }

    private function countTraits(array $state, array $champions, array $items): array
    {
        $championsById = collect($champions)->keyBy(fn (array $champion) => (string) ($champion['id'] ?? ''));
        $itemsById = collect($items)->keyBy(fn (array $item) => (string) ($item['id'] ?? ''))->all();
        $traitCounts = [];

        foreach ($state as $cell) {
            if (! is_array($cell)) {
                continue;
            }

            if (($cell['isSummon'] ?? false) === true) {
                $this->countSummonTraitBonuses($traitCounts, $cell);

                continue;
            }

            if (! isset($cell['championId'])) {
                continue;
            }

            $champion = $championsById->get((string) $cell['championId']);
            if ($champion && isset($champion['traits']) && is_array($champion['traits'])) {
                foreach ($champion['traits'] as $trait) {
                    $traitName = is_array($trait) ? ($trait['name'] ?? null) : $trait;
                    if ($traitName) {
                        $traitCounts[$traitName] = ($traitCounts[$traitName] ?? 0) + 1;
                    }
                }
            }

            foreach (($cell['items'] ?? []) as $itemId) {
                $item = $itemsById[(string) $itemId] ?? null;
                if (! $item || ($item['category'] ?? '') !== 'emblem') {
                    continue;
                }

                $grantedTrait = $item['grantedTrait'] ?? trim(preg_replace('/\s*emblem\s*$/i', '', $item['name'] ?? ''));
                if ($grantedTrait) {
                    $traitCounts[$grantedTrait] = ($traitCounts[$grantedTrait] ?? 0) + 1;
                }
            }
        }

        return $traitCounts;
    }

    private function countSummonTraitBonuses(array &$traitCounts, array $cell): void
    {
        $bonuses = $cell['traitBonuses'] ?? [];

        if (! is_array($bonuses)) {
            return;
        }

        foreach ($bonuses as $traitName) {
            if (! is_string($traitName) || $traitName === '') {
                continue;
            }

            $traitCounts[$traitName] = ($traitCounts[$traitName] ?? 0) + 1;
        }
    }

    private function activeTraits(array $traitCounts, array $traits): array
    {
        $activeTraits = [];

        foreach ($traitCounts as $traitName => $count) {
            $trait = collect($traits)->firstWhere('name', $traitName);
            if (! $trait || ! isset($trait['breakpoints'])) {
                continue;
            }

            $matchedBreakpoint = $this->matchedBreakpoint($trait['breakpoints'], $count);
            if (! $matchedBreakpoint) {
                continue;
            }

            $styleRaw = $matchedBreakpoint['style'] ?? 0;
            $style = is_string($styleRaw) ? (self::STYLE_MAP[$styleRaw] ?? 0) : $styleRaw;

            $activeTraits[] = [
                'id' => $trait['id'],
                'name' => $trait['name'],
                'icon' => $trait['icon'] ?? null,
                'count' => $count,
                'style' => $style,
            ];
        }

        usort($activeTraits, function ($first, $second) {
            return $second['style'] <=> $first['style'] ?: $second['count'] <=> $first['count'];
        });

        return $activeTraits;
    }

    private function matchedBreakpoint(array $breakpoints, int $count): ?array
    {
        $matchedBreakpoint = null;

        foreach ($breakpoints as $breakpoint) {
            if ($count >= $breakpoint['min']) {
                $matchedBreakpoint = $breakpoint;
            }
        }

        return $matchedBreakpoint;
    }

    private function normalizeBoardState(mixed $boardState): array
    {
        if (is_string($boardState)) {
            return json_decode($boardState, true) ?: [];
        }

        return is_array($boardState) ? $boardState : [];
    }
}
