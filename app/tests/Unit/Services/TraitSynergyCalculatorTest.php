<?php

namespace Tests\Unit\Services;

use App\Services\TraitSynergyCalculator;
use Tests\TestCase;

class TraitSynergyCalculatorTest extends TestCase
{
    public function test_empty_board_state_returns_no_traits(): void
    {
        $calculator = new TraitSynergyCalculator;

        $this->assertSame([], $calculator->calculate([], [], [], []));
    }

    public function test_champions_and_emblems_count_traits_with_string_normalized_ids(): void
    {
        $calculator = new TraitSynergyCalculator;

        $traits = $calculator->calculate(
            [
                '0-0' => ['championId' => '1', 'items' => ['100']],
                '0-1' => ['championId' => 2, 'items' => []],
            ],
            [
                ['id' => 1, 'traits' => [['name' => 'Feiticeiro']]],
                ['id' => '2', 'traits' => [['name' => 'Feiticeiro']]],
            ],
            [
                ['id' => 100, 'name' => 'Emblema de Feiticeiro', 'category' => 'emblem', 'grantedTrait' => 'Feiticeiro'],
            ],
            [
                ['id' => 'trait-feiticeiro', 'name' => 'Feiticeiro', 'icon' => 'trait.png', 'breakpoints' => [
                    ['min' => 2, 'style' => 'kBronze'],
                    ['min' => 4, 'style' => 'kGold'],
                ]],
            ],
        );

        $this->assertSame([
            [
                'id' => 'trait-feiticeiro',
                'name' => 'Feiticeiro',
                'icon' => 'trait.png',
                'count' => 3,
                'style' => 1,
            ],
        ], $traits);
    }

    public function test_emblem_name_is_used_when_granted_trait_is_missing(): void
    {
        $calculator = new TraitSynergyCalculator;

        $traits = $calculator->calculate(
            [
                '0-0' => ['championId' => 'TFT_Ahri', 'items' => ['TFT_Item_VanguardEmblem']],
            ],
            [
                ['id' => 'TFT_Ahri', 'traits' => []],
            ],
            [
                ['id' => 'TFT_Item_VanguardEmblem', 'name' => 'Vanguarda Emblem', 'category' => 'emblem'],
            ],
            [
                ['id' => 'trait-vanguarda', 'name' => 'Vanguarda', 'breakpoints' => [
                    ['min' => 1, 'style' => 'kSilver'],
                ]],
            ],
        );

        $this->assertSame('Vanguarda', $traits[0]['name']);
        $this->assertSame(2, $traits[0]['style']);
    }

    public function test_summons_count_trait_bonuses_without_counting_champion_traits(): void
    {
        $calculator = new TraitSynergyCalculator;

        $traits = $calculator->calculate(
            [
                '0-0' => [
                    'championId' => 'TFT_Summon',
                    'isSummon' => true,
                    'traitBonuses' => ['Invocador'],
                ],
            ],
            [
                ['id' => 'TFT_Summon', 'traits' => [['name' => 'Ignorado']]],
            ],
            [],
            [
                ['id' => 'trait-invocador', 'name' => 'Invocador', 'breakpoints' => [
                    ['min' => 1, 'style' => 'kUnique'],
                ]],
                ['id' => 'trait-ignorado', 'name' => 'Ignorado', 'breakpoints' => [
                    ['min' => 1, 'style' => 'kGold'],
                ]],
            ],
        );

        $this->assertCount(1, $traits);
        $this->assertSame('Invocador', $traits[0]['name']);
        $this->assertSame(4, $traits[0]['style']);
    }
}
