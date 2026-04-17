<?php

namespace Database\Factories;

use App\Models\Composition;
use App\Models\CompositionDisposition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompositionDisposition>
 */
class CompositionDispositionFactory extends Factory
{
    protected $model = CompositionDisposition::class;

    public function definition(): array
    {
        return [
            'composition_id' => Composition::factory(),
            'type' => 'champion',
            'champion_ids' => ['TFT_Ahri', 'TFT_Jinx'],
            'star_level' => 1,
            'trait_id' => null,
            'trait_count' => null,
            'item_ids' => ['TFT_Item_1'],
            'priority' => 0,
        ];
    }

    public function trait(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'trait',
            'champion_ids' => null,
            'star_level' => null,
            'trait_id' => 'Set13_Sorcerer',
            'trait_count' => 4,
            'item_ids' => null,
        ]);
    }

    public function item(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'item',
            'champion_ids' => null,
            'star_level' => null,
            'trait_id' => null,
            'trait_count' => null,
            'item_ids' => ['TFT_Item_1', 'TFT_Item_2'],
        ]);
    }
}
