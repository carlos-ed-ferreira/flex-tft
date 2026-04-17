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
