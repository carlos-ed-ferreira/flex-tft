<?php

namespace Database\Factories;

use App\Models\Composition;
use App\Models\CompositionLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompositionLevel>
 */
class CompositionLevelFactory extends Factory
{
    protected $model = CompositionLevel::class;

    public function definition(): array
    {
        return [
            'composition_id' => Composition::factory(),
            'level' => fake()->numberBetween(3, 10),
            'board_state' => [
                '0' => ['championId' => 'TFT_Ahri', 'items' => ['TFT_Item_1', 'TFT_Item_2', 'TFT_Item_3']],
                '1' => ['championId' => 'TFT_Jinx', 'items' => []],
            ],
        ];
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'board_state' => [],
        ]);
    }
}
