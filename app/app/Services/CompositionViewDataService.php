<?php

namespace App\Services;

use App\Models\Composition;
use App\Models\CompositionLevel;
use App\Support\TftLevels;

class CompositionViewDataService
{
    public function __construct(
        private TftDataService $tftData,
        private TraitSynergyCalculator $traitSynergyCalculator,
    ) {}

    public function card(Composition $composition): array
    {
        $highestLevel = $this->highestLevelWithContent($composition);

        return [
            'id' => $composition->id,
            'name' => $composition->name,
            'notes' => $composition->notes,
            'is_global' => $composition->is_global,
            'user_id' => $composition->user_id,
            'author' => $composition->user?->nickname,
            'traits' => $highestLevel ? $this->traits($highestLevel->board_state) : [],
            'champions' => $highestLevel ? $this->championsWithThreeItems($highestLevel->board_state) : [],
            'dispositions' => $this->dispositions($composition),
        ];
    }

    public function show(Composition $composition): array
    {
        return [
            'composition' => [
                'id' => $composition->id,
                'name' => $composition->name,
                'notes' => $composition->notes,
                'is_global' => $composition->is_global,
                'user_id' => $composition->user_id,
                'author' => $composition->user?->nickname,
            ],
            'levels' => $this->levels($composition),
            'dispositions' => $this->dispositions($composition),
        ];
    }

    public function editor(Composition $composition): array
    {
        return [
            'composition' => [
                'id' => $composition->id,
                'name' => $composition->name,
                'notes' => $composition->notes,
            ],
            'levels' => $this->levels($composition),
            'dispositions' => $this->dispositions($composition),
        ];
    }

    public function emptyLevels(): array
    {
        return collect(TftLevels::values())->map(fn (int $level) => [
            'level' => $level,
            'versions' => [
                ['version' => 1, 'label' => null, 'board_state' => (object) []],
            ],
        ])->all();
    }

    public function dispositions(Composition $composition)
    {
        return $composition->dispositions->map(fn ($disposition) => [
            'type' => $disposition->type,
            'champion_ids' => $disposition->champion_ids,
            'star_level' => $disposition->star_level,
            'trait_id' => $disposition->trait_id,
            'trait_count' => $disposition->trait_count,
            'item_ids' => $disposition->item_ids,
            'priority' => $disposition->priority,
        ])->values();
    }

    private function levels(Composition $composition): array
    {
        $levelRows = $composition->levels->groupBy('level');

        return collect(TftLevels::values())->map(function (int $level) use ($levelRows) {
            $rows = $levelRows->get($level);

            if ($rows && $rows->isNotEmpty()) {
                $versions = $rows->sortBy('version')->map(fn ($row) => [
                    'version' => $row->version,
                    'label' => $row->label,
                    'board_state' => $row->board_state ?? (object) [],
                ])->values()->all();
            } else {
                $versions = [
                    ['version' => 1, 'label' => null, 'board_state' => (object) []],
                ];
            }

            return ['level' => $level, 'versions' => $versions];
        })->all();
    }

    private function highestLevelWithContent(Composition $composition): ?CompositionLevel
    {
        return $composition->levels
            ->sortByDesc('level')
            ->sortBy('version')
            ->first(function ($level) {
                $state = $this->normalizeBoardState($level->board_state);

                return count($state) > 0;
            });
    }

    private function championsWithThreeItems(mixed $boardState): array
    {
        $state = $this->normalizeBoardState($boardState);

        if (empty($state)) {
            return [];
        }

        $champions = [];
        foreach ($state as $cell) {
            if (isset($cell['championId']) && isset($cell['items']) && count($cell['items']) === 3) {
                $champions[] = [
                    'id' => $cell['championId'],
                    'items' => $cell['items'],
                ];
            }
        }

        return $champions;
    }

    private function traits(mixed $boardState): array
    {
        return $this->traitSynergyCalculator->calculate(
            $boardState,
            $this->tftData->getChampions(),
            $this->tftData->getItems(),
            $this->tftData->getTraits(),
        );
    }

    private function normalizeBoardState(mixed $boardState): array
    {
        if (is_string($boardState)) {
            return json_decode($boardState, true) ?: [];
        }

        return is_array($boardState) ? $boardState : [];
    }
}
