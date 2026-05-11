<?php

namespace Tests\Concerns;

use App\Models\User;
use RuntimeException;

trait CreatesUsers
{
    protected function makeUser(array $attributes = []): User
    {
        $user = User::factory()->createOne($attributes);

        if (! $user instanceof User) {
            throw new RuntimeException('Expected user factory to create a User instance.');
        }

        return $user;
    }

    protected function makeAdminUser(array $attributes = []): User
    {
        $user = User::factory()->admin()->createOne($attributes);

        if (! $user instanceof User) {
            throw new RuntimeException('Expected user factory to create a User instance.');
        }

        return $user;
    }

    protected function makeUnverifiedUser(array $attributes = []): User
    {
        $user = User::factory()->unverified()->createOne($attributes);

        if (! $user instanceof User) {
            throw new RuntimeException('Expected user factory to create a User instance.');
        }

        return $user;
    }
}
