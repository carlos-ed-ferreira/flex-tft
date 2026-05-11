<?php

namespace Tests\Feature;

use App\Models\Composition;
use App\Models\CompositionDisposition;
use App\Models\CompositionLevel;
use App\Services\TftDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class CompositionAuthTest extends TestCase
{
    use CreatesUsers;
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
                ['level' => 3, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 4, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 5, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 6, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 7, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 8, 'version' => 1, 'label' => null, 'board_state' => [
                    '0' => ['championId' => 'TFT_Ahri', 'items' => ['TFT_Item_1']],
                ]],
                ['level' => 9, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 10, 'version' => 1, 'label' => null, 'board_state' => (object) []],
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

    private function plannerBoard(array $championIds): array
    {
        $boardState = [];

        foreach ($championIds as $index => $championId) {
            $boardState["0-{$index}"] = ['championId' => $championId, 'items' => []];
        }

        return $boardState;
    }

    public function test_my_index_requires_auth(): void
    {
        $response = $this->get('/my-compositions');

        $response->assertRedirect('/login');
    }

    public function test_my_index_shows_user_compositions(): void
    {
        $this->mockTftData();

        $user = $this->makeUser();
        Composition::factory()->for($user)->create(['name' => 'My Comp']);
        Composition::factory()->create(['name' => 'Other Comp']);

        $response = $this->actingAs($user)->get('/my-compositions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/MyIndex')
            ->has('compositions', 1)
            ->where('compositions.0.name', 'My Comp')
        );
    }

    public function test_create_requires_auth(): void
    {
        $response = $this->get('/compositions-create');

        $response->assertRedirect('/login');
    }

    public function test_create_returns_editor(): void
    {
        $this->mockTftData();

        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/compositions-create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/Editor')
            ->where('composition', null)
            ->has('levels', 8)
            ->has('levels.0.level')
            ->has('levels.0.versions')
            ->has('tftData')
        );
    }

    public function test_store_creates_composition_with_levels_and_dispositions(): void
    {
        $user = $this->makeUser();

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

    public function test_store_persists_multiple_versions_for_same_level(): void
    {
        $user = $this->makeUser();

        $payload = $this->validCompositionPayload([
            'levels' => [
                ['level' => 3, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 4, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 5, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 6, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 7, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 8, 'version' => 1, 'label' => 'Principal', 'board_state' => [
                    '0' => ['championId' => 'TFT_Ahri', 'items' => []],
                ]],
                ['level' => 8, 'version' => 2, 'label' => 'Alternativa', 'board_state' => [
                    '0' => ['championId' => 'TFT_Lux', 'items' => []],
                ]],
                ['level' => 9, 'version' => 1, 'label' => null, 'board_state' => (object) []],
                ['level' => 10, 'version' => 1, 'label' => null, 'board_state' => (object) []],
            ],
        ]);

        $this->actingAs($user)->post('/compositions', $payload);

        $composition = Composition::where('user_id', $user->id)->first();
        $this->assertCount(9, $composition->levels);

        $this->assertDatabaseHas('composition_levels', [
            'composition_id' => $composition->id,
            'level' => 8,
            'version' => 1,
            'label' => 'Principal',
        ]);
        $this->assertDatabaseHas('composition_levels', [
            'composition_id' => $composition->id,
            'level' => 8,
            'version' => 2,
            'label' => 'Alternativa',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/compositions', []);

        $response->assertSessionHasErrors(['name', 'levels']);
    }

    public function test_store_rejects_invalid_level_value(): void
    {
        $user = $this->makeUser();

        $payload = $this->validCompositionPayload([
            'levels' => [
                ['level' => 11, 'version' => 1, 'label' => null, 'board_state' => (object) []],
            ],
        ]);

        $response = $this->actingAs($user)->post('/compositions', $payload);

        $response->assertSessionHasErrors(['levels.0.level']);
    }

    public function test_store_rejects_invalid_disposition_type(): void
    {
        $user = $this->makeUser();

        $payload = $this->validCompositionPayload([
            'dispositions' => [
                ['type' => 'summon'],
            ],
        ]);

        $response = $this->actingAs($user)->post('/compositions', $payload);

        $response->assertSessionHasErrors(['dispositions.0.type']);
    }

    public function test_store_requires_auth(): void
    {
        $response = $this->post('/compositions', $this->validCompositionPayload());

        $response->assertRedirect('/login');
    }

    public function test_edit_returns_editor_for_owner(): void
    {
        $this->mockTftData();

        $user = $this->makeUser();
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

        $owner = $this->makeUser();
        $admin = $this->makeAdminUser();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($admin)->get("/compositions/{$composition->id}/edit");

        $response->assertStatus(200);
    }

    public function test_edit_by_non_owner_returns_403(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->get("/compositions/{$composition->id}/edit");

        $response->assertStatus(403);
    }

    public function test_update_composition(): void
    {
        $user = $this->makeUser();
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
        $user = $this->makeUser();
        $composition = Composition::factory()->for($user)->create();

        $response = $this->actingAs($user)->put("/compositions/{$composition->id}", []);

        $response->assertSessionHasErrors(['name', 'levels']);
    }

    public function test_update_rejects_invalid_disposition_type(): void
    {
        $user = $this->makeUser();
        $composition = Composition::factory()->for($user)->create();

        $payload = $this->validCompositionPayload([
            'dispositions' => [
                ['type' => 'summon'],
            ],
        ]);

        $response = $this->actingAs($user)->put("/compositions/{$composition->id}", $payload);

        $response->assertSessionHasErrors(['dispositions.0.type']);
    }

    public function test_update_by_non_owner_returns_403(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->put(
            "/compositions/{$composition->id}",
            $this->validCompositionPayload()
        );

        $response->assertStatus(403);
    }

    public function test_destroy_composition(): void
    {
        $user = $this->makeUser();
        $composition = Composition::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/compositions/{$composition->id}");

        $response->assertRedirect(route('compositions.my'));
        $this->assertDatabaseMissing('compositions', ['id' => $composition->id]);
    }

    public function test_destroy_by_non_owner_returns_403(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->delete("/compositions/{$composition->id}");

        $response->assertStatus(403);
    }

    public function test_duplicate_composition(): void
    {
        $user = $this->makeUser();
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
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $composition = Composition::factory()->for($owner)->create();

        $response = $this->actingAs($other)->post("/compositions/{$composition->id}/duplicate");

        $response->assertStatus(403);
    }

    public function test_import_global_composition(): void
    {
        $user = $this->makeUser();
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
        $user = $this->makeUser();
        $privateComp = Composition::factory()->create(['is_global' => false]);

        $response = $this->actingAs($user)->post("/compositions/{$privateComp->id}/import");

        $response->assertStatus(403);
    }

    public function test_export_planner_code_for_global_composition_as_guest(): void
    {
        $composition = Composition::factory()->global()->create();
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 8,
            'version' => 1,
            'board_state' => $this->plannerBoard([
                'TFT17_MasterYi',
                'TFT17_TahmKench',
                'TFT17_Fiora',
                'TFT17_Gragas',
                'TFT17_Maokai',
                'TFT17_Urgot',
                'TFT17_Belveth',
                'TFT17_Kindred',
            ]),
        ]);

        $response = $this->getJson(route('compositions.planner.export', $composition));

        $response->assertOk()->assertJson([
            'code' => '0200f03101e02401f02f04f013000000TFTSet17',
            'level' => 8,
            'version' => 1,
        ]);
    }

    public function test_export_planner_code_uses_version_one_of_highest_level_with_champions(): void
    {
        $composition = Composition::factory()->global()->create();
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 8,
            'version' => 1,
            'board_state' => $this->plannerBoard(['TFT17_MasterYi', 'TFT17_TahmKench']),
        ]);
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 9,
            'version' => 2,
            'board_state' => $this->plannerBoard(['TFT17_Fiora']),
        ]);
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 10,
            'version' => 1,
            'board_state' => [],
        ]);

        $response = $this->getJson(route('compositions.planner.export', $composition));

        $response->assertOk()->assertJson([
            'code' => '0200f031000000000000000000000000TFTSet17',
            'level' => 8,
            'version' => 1,
        ]);
    }

    public function test_owner_can_export_private_planner_code(): void
    {
        $owner = $this->makeUser();
        $composition = Composition::factory()->for($owner)->create();
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 3,
            'version' => 1,
            'board_state' => $this->plannerBoard(['TFT17_MasterYi']),
        ]);

        $response = $this->actingAs($owner)->getJson(route('compositions.planner.export', $composition));

        $response->assertOk()->assertJson([
            'code' => '0200f000000000000000000000000000TFTSet17',
        ]);
    }

    public function test_admin_can_export_private_planner_code(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeAdminUser();
        $composition = Composition::factory()->for($owner)->create();
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 3,
            'version' => 1,
            'board_state' => $this->plannerBoard(['TFT17_MasterYi']),
        ]);

        $response = $this->actingAs($admin)->getJson(route('compositions.planner.export', $composition));

        $response->assertOk()->assertJson([
            'code' => '0200f000000000000000000000000000TFTSet17',
        ]);
    }

    public function test_non_owner_cannot_export_private_planner_code(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $composition = Composition::factory()->for($owner)->create();
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 3,
            'version' => 1,
            'board_state' => $this->plannerBoard(['TFT17_MasterYi']),
        ]);

        $response = $this->actingAs($other)->getJson(route('compositions.planner.export', $composition));

        $response->assertStatus(403);
    }

    public function test_import_planner_code_requires_auth(): void
    {
        $response = $this->post(route('compositions.planner.import'), [
            'code' => '0200f03101e02401f02f04f013000000TFTSet17',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_import_planner_code_creates_composition(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('compositions.planner.import'), [
            'code' => '0200f03101e02401f02f04f013000000TFTSet17',
        ]);

        $composition = Composition::where('user_id', $user->id)->first();
        $response->assertRedirect(route('compositions.edit', $composition));

        $this->assertSame('Composição importada do planner', $composition->name);
        $this->assertCount(8, $composition->levels);

        $level = $composition->levels()->where('level', 8)->where('version', 1)->first();
        $this->assertSame('TFT17_MasterYi', $level->board_state['0-0']['championId']);
        $this->assertSame('TFT17_Kindred', $level->board_state['1-0']['championId']);
    }

    public function test_import_planner_code_rejects_invalid_code(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post(route('compositions.planner.import'), [
            'code' => 'codigo-invalido',
        ]);

        $response->assertSessionHasErrors(['code']);
    }
}
