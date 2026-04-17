<?php

namespace Tests\Feature;

use App\Models\Composition;
use App\Models\CompositionDisposition;
use App\Models\CompositionLevel;
use App\Models\User;
use App\Services\TftDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompositionAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mockTftData(): void
    {
        $this->mock(TftDataService::class, function ($mock) {
            $mock->shouldReceive('all')->andReturn([
                'champions' => [],
                'items' => [],
                'traits' => [],
            ]);
        });
    }

    private function validCompositionPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Composition',
            'notes' => 'Some notes',
            'levels' => [
                ['level' => 3, 'board_state' => (object) []],
                ['level' => 4, 'board_state' => (object) []],
                ['level' => 5, 'board_state' => (object) []],
                ['level' => 6, 'board_state' => (object) []],
                ['level' => 7, 'board_state' => (object) []],
                ['level' => 8, 'board_state' => [
                    '0' => ['championId' => 'TFT_Ahri', 'items' => ['TFT_Item_1']],
                ]],
                ['level' => 9, 'board_state' => (object) []],
                ['level' => 10, 'board_state' => (object) []],
            ],
            'dispositions' => [
                [
                    'type' => 'champion',
                    'champion_ids' => ['TFT_Ahri'],
                    'star_level' => 1,
                    'trait_id' => null,
                    'trait_count' => null,
                    'item_ids' => ['TFT_Item_1'],
                    'priority' => 0,
                ],
            ],
        ], $overrides);
    }

    // ── my-compositions ─────────────────────────────────

    public function test_my_index_requires_auth(): void
    {
        $response = $this->get('/my-compositions');

        $response->assertRedirect('/login');
    }

    public function test_my_index_shows_user_compositions(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();
        Composition::factory()->for($user)->create(['name' => 'My Comp']);
        Composition::factory()->create(['name' => 'Other Comp']); // another user's

        $response = $this->actingAs($user)->get('/my-compositions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/MyIndex')
            ->has('compositions', 1)
            ->where('compositions.0.name', 'My Comp')
        );
    }

    // ── create ──────────────────────────────────────────

    public function test_create_requires_auth(): void
    {
        $response = $this->get('/compositions-create');

        $response->assertRedirect('/login');
    }

    public function test_create_returns_editor(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/compositions-create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/Editor')
            ->where('composition', null)
            ->has('levels', 8)
            ->has('tftData')
        );
    }

    // ── store ───────────────────────────────────────────

    public function test_store_creates_composition_with_levels_and_dispositions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/compositions', $this->validCompositionPayload());

        $response->assertRedirect(route('compositions.my'));

        $this->assertDatabaseHas('compositions', [
            'user_id' => $user->id,
            'name' => 'Test Composition',
            'notes' => 'Some notes',
        ]);

        $composition = Composition::where('user_id', $user->id)->first();
        $this->assertCount(8, $composition->levels);
        $this->assertCount(1, $composition->dispositions);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/compositions', []);

        $response->assertSessionHasErrors(['name', 'levels']);
    }

    public function test_store_requires_auth(): void
    {
        $response = $this->post('/compositions', $this->validCompositionPayload());

        $response->assertRedirect('/login');
    }

    // ── edit ────────────────────────────────────────────

    public function test_edit_returns_editor_for_owner(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();
        $composition = Composition::factory()->for($user)->create();
        CompositionLevel::factory()->create(['composition_id' => $composition->id, 'level' => 8]);

        $response = $this->actingAs($user)->get("/compositions/{$composition->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/Editor')
            ->has('composition')
        );
    }

    public function test_edit_as_admin_works(): void
    {
        $this->mockTftData();

        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($admin)->get("/compositions/{$composition->id}/edit");

        $response->assertStatus(200);
    }

    public function test_edit_by_non_owner_returns_403(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->get("/compositions/{$composition->id}/edit");

        $response->assertStatus(403);
    }

    // ── update ──────────────────────────────────────────

    public function test_update_composition(): void
    {
        $user = User::factory()->create();
        $composition = Composition::factory()->for($user)->create();
        CompositionLevel::factory()->create(['composition_id' => $composition->id, 'level' => 8]);

        $response = $this->actingAs($user)->put(
            "/compositions/{$composition->id}",
            $this->validCompositionPayload(['name' => 'Updated Name'])
        );

        $response->assertRedirect(route('compositions.my'));
        $this->assertDatabaseHas('compositions', [
            'id' => $composition->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $composition = Composition::factory()->for($user)->create();

        $response = $this->actingAs($user)->put("/compositions/{$composition->id}", []);

        $response->assertSessionHasErrors(['name', 'levels']);
    }

    public function test_update_by_non_owner_returns_403(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->put(
            "/compositions/{$composition->id}",
            $this->validCompositionPayload()
        );

        $response->assertStatus(403);
    }

    // ── destroy ─────────────────────────────────────────

    public function test_destroy_composition(): void
    {
        $user = User::factory()->create();
        $composition = Composition::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/compositions/{$composition->id}");

        $response->assertRedirect(route('compositions.my'));
        $this->assertDatabaseMissing('compositions', ['id' => $composition->id]);
    }

    public function test_destroy_by_non_owner_returns_403(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->delete("/compositions/{$composition->id}");

        $response->assertStatus(403);
    }

    // ── duplicate ───────────────────────────────────────

    public function test_duplicate_composition(): void
    {
        $user = User::factory()->create();
        $composition = Composition::factory()->for($user)->create(['name' => 'Original']);
        CompositionLevel::factory()->create(['composition_id' => $composition->id, 'level' => 8]);
        CompositionDisposition::factory()->create(['composition_id' => $composition->id]);

        $response = $this->actingAs($user)->post("/compositions/{$composition->id}/duplicate");

        $response->assertRedirect();
        $this->assertDatabaseHas('compositions', [
            'user_id' => $user->id,
            'name' => 'Original (cópia)',
        ]);
        $this->assertEquals(2, Composition::where('user_id', $user->id)->count());
    }

    public function test_duplicate_by_non_owner_returns_403(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->post("/compositions/{$composition->id}/duplicate");

        $response->assertStatus(403);
    }

    // ── import ──────────────────────────────────────────

    public function test_import_global_composition(): void
    {
        $user = User::factory()->create();
        $globalComp = Composition::factory()->global()->create(['name' => 'Global Comp']);
        CompositionLevel::factory()->create(['composition_id' => $globalComp->id, 'level' => 8]);
        CompositionDisposition::factory()->create(['composition_id' => $globalComp->id]);

        $response = $this->actingAs($user)->post("/compositions/{$globalComp->id}/import");

        $response->assertRedirect(route('compositions.my'));
        $this->assertDatabaseHas('compositions', [
            'user_id' => $user->id,
            'name' => 'Global Comp',
        ]);
    }

    public function test_import_private_composition_returns_403(): void
    {
        $user = User::factory()->create();
        $privateComp = Composition::factory()->create(['is_global' => false]);

        $response = $this->actingAs($user)->post("/compositions/{$privateComp->id}/import");

        $response->assertStatus(403);
    }
}
