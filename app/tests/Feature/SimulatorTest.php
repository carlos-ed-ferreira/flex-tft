<?php

namespace Tests\Feature;

use App\Models\Composition;
use App\Models\CompositionDisposition;
use App\Models\User;
use App\Services\TftDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulatorTest extends TestCase
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

    public function test_simulator_index_as_guest_shows_global_compositions(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();

        $global = Composition::factory()->global()->for($user)->create();
        CompositionDisposition::factory()->create(['composition_id' => $global->id]);

        $private = Composition::factory()->for($user)->create(['is_global' => false]);
        CompositionDisposition::factory()->create(['composition_id' => $private->id]);

        Composition::factory()->global()->for($user)->create();

        $response = $this->get('/simulator');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Simulator/Index')
            ->has('compositions', 1)
            ->has('tftData')
        );
    }

    public function test_simulator_index_as_user_shows_global_and_own_compositions(): void
    {
        $this->mockTftData();

        $user = User::factory()->create();
        $other = User::factory()->create();

        $global = Composition::factory()->global()->for($other)->create();
        CompositionDisposition::factory()->create(['composition_id' => $global->id]);

        $own = Composition::factory()->for($user)->create(['is_global' => false]);
        CompositionDisposition::factory()->create(['composition_id' => $own->id]);

        $otherPrivate = Composition::factory()->for($other)->create(['is_global' => false]);
        CompositionDisposition::factory()->create(['composition_id' => $otherPrivate->id]);

        $response = $this->actingAs($user)->get('/simulator');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Simulator/Index')
            ->has('compositions', 2)
        );
    }
}
