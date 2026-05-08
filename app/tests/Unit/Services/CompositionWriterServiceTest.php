<?php

namespace Tests\Unit\Services;

use App\Models\Composition;
use App\Models\CompositionDisposition;
use App\Models\CompositionLevel;
use App\Models\User;
use App\Services\CompositionWriterService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompositionWriterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_for_user_persists_composition_levels_and_dispositions(): void
    {
        $user = User::factory()->create();
        $service = app(CompositionWriterService::class);

        $composition = $service->createForUser($user->id, $this->payload());

        $this->assertDatabaseHas('compositions', [
            'id' => $composition->id,
            'user_id' => $user->id,
            'name' => 'Writer Comp',
        ]);
        $this->assertCount(2, $composition->levels()->get());
        $this->assertCount(1, $composition->dispositions()->get());
    }

    public function test_update_replaces_dispositions_and_updates_levels(): void
    {
        $user = User::factory()->create();
        $composition = Composition::factory()->for($user)->create(['name' => 'Old Name']);
        CompositionLevel::factory()->create(['composition_id' => $composition->id, 'level' => 3]);
        CompositionDisposition::factory()->create(['composition_id' => $composition->id, 'priority' => 0]);

        $service = app(CompositionWriterService::class);
        $service->update($composition, $this->payload(['name' => 'New Name']));

        $this->assertDatabaseHas('compositions', [
            'id' => $composition->id,
            'name' => 'New Name',
        ]);
        $this->assertCount(2, $composition->levels()->get());
        $this->assertCount(1, $composition->dispositions()->get());
    }

    public function test_create_rolls_back_when_disposition_write_fails(): void
    {
        $user = User::factory()->create();
        $service = app(CompositionWriterService::class);

        try {
            $service->createForUser($user->id, $this->payload([
                'name' => 'Broken Comp',
                'dispositions' => [
                    ['type' => 'invalid'],
                ],
            ]));
        } catch (QueryException) {
        }

        $this->assertDatabaseMissing('compositions', [
            'user_id' => $user->id,
            'name' => 'Broken Comp',
        ]);
    }

    public function test_duplicate_for_user_copies_related_data(): void
    {
        $user = User::factory()->create();
        $source = Composition::factory()->for($user)->create(['name' => 'Original']);
        CompositionLevel::factory()->create(['composition_id' => $source->id, 'level' => 8]);
        CompositionDisposition::factory()->create(['composition_id' => $source->id]);

        $service = app(CompositionWriterService::class);
        $copy = $service->duplicateForUser($source, $user->id);

        $this->assertSame('Original (cópia)', $copy->name);
        $this->assertCount(1, $copy->levels()->get());
        $this->assertCount(1, $copy->dispositions()->get());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Writer Comp',
            'notes' => 'Notes',
            'levels' => [
                ['level' => 3, 'board_state' => (object) []],
                ['level' => 4, 'board_state' => [
                    '0-0' => ['championId' => 'TFT_Ahri', 'items' => []],
                ]],
            ],
            'dispositions' => [
                [
                    'type' => 'champion',
                    'champion_ids' => ['TFT_Ahri'],
                    'star_level' => 1,
                    'trait_id' => null,
                    'trait_count' => null,
                    'item_ids' => [],
                    'priority' => 0,
                ],
            ],
        ], $overrides);
    }
}
