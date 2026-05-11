<?php

namespace App\Services;

use App\Models\Composition;
use Illuminate\Support\Facades\DB;

class CompositionWriterService
{
    public function createForUser(int $userId, array $data): Composition
    {
        return DB::transaction(function () use ($userId, $data) {
            $composition = Composition::create([
                'user_id' => $userId,
                'name' => $data['name'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLevels($composition, $data['levels']);
            $this->syncDispositions($composition, $data['dispositions'] ?? []);

            return $composition;
        });
    }

    public function update(Composition $composition, array $data): Composition
    {
        return DB::transaction(function () use ($composition, $data) {
            $composition->update([
                'name' => $data['name'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLevels($composition, $data['levels']);
            $this->syncDispositions($composition, $data['dispositions'] ?? []);

            return $composition;
        });
    }

    public function duplicateForUser(Composition $source, int $userId): Composition
    {
        return $this->copyForUser($source, $userId, $source->name . ' (cópia)');
    }

    public function importForUser(Composition $source, int $userId): Composition
    {
        return $this->copyForUser($source, $userId, $source->name);
    }

    private function copyForUser(Composition $source, int $userId, string $name): Composition
    {
        return DB::transaction(function () use ($source, $userId, $name) {
            $source->loadMissing(['levels', 'dispositions']);

            $composition = Composition::create([
                'user_id' => $userId,
                'name' => $name,
                'notes' => $source->notes,
            ]);

            foreach ($source->levels as $level) {
                $composition->levels()->create([
                    'level' => $level->level,
                    'version' => $level->version,
                    'label' => $level->label,
                    'board_state' => $level->board_state,
                ]);
            }

            foreach ($source->dispositions as $disposition) {
                $composition->dispositions()->create([
                    'type' => $disposition->type,
                    'champion_ids' => $disposition->champion_ids,
                    'star_level' => $disposition->star_level,
                    'trait_id' => $disposition->trait_id,
                    'trait_count' => $disposition->trait_count,
                    'item_ids' => $disposition->item_ids,
                    'priority' => $disposition->priority,
                ]);
            }

            return $composition;
        });
    }

    private function syncLevels(Composition $composition, array $levels): void
    {
        $composition->levels()->delete();

        foreach ($levels as $levelData) {
            $composition->levels()->create([
                'level' => $levelData['level'],
                'version' => $levelData['version'],
                'label' => $levelData['label'] ?? null,
                'board_state' => $levelData['board_state'],
            ]);
        }
    }

    private function syncDispositions(Composition $composition, array $dispositions): void
    {
        $composition->dispositions()->delete();

        foreach ($dispositions as $index => $disposition) {
            $composition->dispositions()->create([
                'type' => $disposition['type'],
                'champion_ids' => $disposition['champion_ids'] ?? null,
                'star_level' => $disposition['star_level'] ?? null,
                'trait_id' => $disposition['trait_id'] ?? null,
                'trait_count' => $disposition['trait_count'] ?? null,
                'item_ids' => $disposition['item_ids'] ?? null,
                'priority' => $disposition['priority'] ?? $index,
            ]);
        }
    }
}
