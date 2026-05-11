<?php

namespace Tests\Feature;

use App\Models\Composition;
use App\Models\CompositionLevel;
use App\Models\User;
use App\Services\TftDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompositionPublicTest extends TestCase
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

    private function mockTftDataForCards(): void
    {
        $this->mock(TftDataService::class, function ($mock) {
            $mock->shouldReceive('all')->andReturn([
                'champions' => [],
                'items' => [],
                'traits' => [],
            ]);
            $mock->shouldReceive('getChampions')->andReturn([
                ['id' => 'TFT_Ahri', 'traits' => [['name' => 'Feiticeiro']]],
                ['id' => 'TFT_Lux', 'traits' => [['name' => 'Feiticeiro']]],
            ]);
            $mock->shouldReceive('getItems')->andReturn([
                ['id' => 'TFT_Item_1', 'name' => 'Espada', 'category' => 'component'],
                ['id' => 'TFT_Item_2', 'name' => 'Armadura', 'category' => 'component'],
                ['id' => 'TFT_Item_3', 'name' => 'Feiticeiro Emblem', 'category' => 'emblem', 'grantedTrait' => 'Feiticeiro'],
            ]);
            $mock->shouldReceive('getTraits')->andReturn([
                ['id' => 'trait-feiticeiro', 'name' => 'Feiticeiro', 'icon' => 'trait.png', 'breakpoints' => [
                    ['min' => 2, 'style' => 'kBronze'],
                ]],
            ]);
        });
    }

    public function test_index_returns_global_compositions(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();
        Composition::factory()->global()->for($user)->create(['name' => 'Global Comp']);
        Composition::factory()->for($user)->create(['name' => 'Private Comp']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/Index')
            ->has('compositions', 1)
            ->where('compositions.0.name', 'Global Comp')
        );
    }

    public function test_index_maps_card_traits_and_three_item_champions(): void
    {
        $this->mockTftDataForCards();

        $user = User::factory()->create();
        $composition = Composition::factory()->global()->for($user)->create(['name' => 'Global Comp']);
        CompositionLevel::factory()->create([
            'composition_id' => $composition->id,
            'level' => 8,
            'board_state' => [
                '0-0' => ['championId' => 'TFT_Ahri', 'items' => ['TFT_Item_1', 'TFT_Item_2', 'TFT_Item_3']],
                '0-1' => ['championId' => 'TFT_Lux', 'items' => []],
            ],
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/Index')
            ->where('compositions.0.traits.0.name', 'Feiticeiro')
            ->where('compositions.0.traits.0.count', 3)
            ->where('compositions.0.champions.0.id', 'TFT_Ahri')
        );
    }

    public function test_show_global_composition_as_guest(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();
        $composition = Composition::factory()->global()->for($user)->create();
        CompositionLevel::factory()->create(['composition_id' => $composition->id, 'level' => 8]);

        $response = $this->get("/compositions/{$composition->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/Show')
            ->has('composition')
            ->has('levels', 8)
            ->has('levels.0.level')
            ->has('levels.0.versions')
            ->has('tftData')
        );
    }

    public function test_show_private_composition_as_guest_returns_403(): void
    {
        $this->mockTftData();

        $composition = Composition::factory()->create(['is_global' => false]);

        $response = $this->get("/compositions/{$composition->id}");

        $response->assertStatus(403);
    }

    public function test_show_private_composition_as_owner(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();
        $composition = Composition::factory()->for($user)->create(['is_global' => false]);
        CompositionLevel::factory()->create(['composition_id' => $composition->id, 'level' => 8]);

        $response = $this->actingAs($user)->get("/compositions/{$composition->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Compositions/Show')
            ->has('composition')
        );
    }

    public function test_show_private_composition_as_admin(): void
    {
        $this->mockTftData();

        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $composition = Composition::factory()->for($owner)->create(['is_global' => false]);

        $response = $this->actingAs($admin)->get("/compositions/{$composition->id}");

        $response->assertStatus(200);
    }

    public function test_show_private_composition_as_other_user_returns_403(): void
    {
        $this->mockTftData();

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $composition = Composition::factory()->for($owner)->create(['is_global' => false]);

        $response = $this->actingAs($other)->get("/compositions/{$composition->id}");

        $response->assertStatus(403);
    }
}
