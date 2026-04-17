<?php

namespace Database\Factories;

use App\Models\Composition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Composition>
 */
class CompositionFactory extends Factory
{
    protected $model = Composition::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'notes' => fake()->optional()->sentence(),
            'is_global' => false,
        ];
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_global' => true,
        ]);
    }
}
