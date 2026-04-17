<?php

namespace Tests\Feature\Admin;

use App\Models\Composition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_global_requires_admin(): void
    {
        $user = User::factory()->create(['role' => 'u']);
        $composition = Composition::factory()->for($user)->create(['is_global' => false]);

        $response = $this->actingAs($user)->put("/admin/compositions/{$composition->id}/toggle-global");

        $response->assertStatus(403);
    }

    public function test_toggle_global_makes_private_composition_global(): void
    {
        $admin = User::factory()->admin()->create();
        $composition = Composition::factory()->for($admin)->create(['is_global' => false]);

        $response = $this->actingAs($admin)->put("/admin/compositions/{$composition->id}/toggle-global");

        $response->assertRedirect();
        $this->assertDatabaseHas('compositions', [
            'id' => $composition->id,
            'is_global' => true,
        ]);
    }

    public function test_toggle_global_makes_global_composition_private(): void
    {
        $admin = User::factory()->admin()->create();
        $composition = Composition::factory()->global()->for($admin)->create();

        $response = $this->actingAs($admin)->put("/admin/compositions/{$composition->id}/toggle-global");

        $response->assertRedirect();
        $this->assertDatabaseHas('compositions', [
            'id' => $composition->id,
            'is_global' => false,
        ]);
    }

    public function test_non_admin_cannot_toggle_global(): void
    {
        $composition = Composition::factory()->create(['is_global' => false]);

        $response = $this->put("/admin/compositions/{$composition->id}/toggle-global");

        $response->assertRedirect('/login');
    }
}
