<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class UserTest extends TestCase
{
    use CreatesUsers;
    use RefreshDatabase;

    public function test_admin_users_index_requires_auth(): void
    {
        $response = $this->get('/admin/users');

        $response->assertRedirect('/login');
    }

    public function test_admin_users_index_requires_admin(): void
    {
        $user = $this->makeUser(['role' => 'u']);

        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_users_index_lists_users(): void
    {
        $admin = $this->makeAdminUser();
        $this->makeUser();
        $this->makeUser();
        $this->makeUser();
        $this->makeUser();
        $this->makeUser();

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 6)
            ->has('filters')
        );
    }

    public function test_admin_users_index_filters_by_search(): void
    {
        $admin = $this->makeAdminUser();
        $this->makeUser(['nickname' => 'target_user', 'email' => 'target@test.com']);
        $this->makeUser(['nickname' => 'other_user', 'email' => 'other@test.com']);

        $response = $this->actingAs($admin)->get('/admin/users?search=target');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
            ->where('users.data.0.nickname', 'target_user')
        );
    }

    public function test_admin_users_index_filters_by_role(): void
    {
        $admin = $this->makeAdminUser();
        $this->makeUser(['role' => 'u']);
        $this->makeUser(['role' => 'u']);
        $this->makeUser(['role' => 'u']);

        $response = $this->actingAs($admin)->get('/admin/users?role=a');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 1)
        );
    }

    public function test_admin_update_role(): void
    {
        $admin = $this->makeAdminUser();
        $user = $this->makeUser(['role' => 'u']);

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}/role", [
            'role' => 'a',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'a',
        ]);
    }

    public function test_admin_update_role_validates(): void
    {
        $admin = $this->makeAdminUser();
        $user = $this->makeUser();

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}/role", [
            'role' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['role']);
    }

    public function test_non_admin_cannot_update_role(): void
    {
        $user = $this->makeUser(['role' => 'u']);
        $target = $this->makeUser();

        $response = $this->actingAs($user)->put("/admin/users/{$target->id}/role", [
            'role' => 'a',
        ]);

        $response->assertStatus(403);
    }
}
