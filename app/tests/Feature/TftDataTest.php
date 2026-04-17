<?php

namespace Tests\Feature;

use App\Services\TftDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TftDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_tft_data_returns_json_structure(): void
    {
        $this->mock(TftDataService::class, function ($mock) {
            $mock->shouldReceive('all')->once()->andReturn([
                'champions' => [['id' => 'TFT_Ahri', 'name' => 'Ahri']],
                'items' => [['id' => 'TFT_Item_1', 'name' => 'Sword']],
                'traits' => [['id' => 'Set13_Sorcerer', 'name' => 'Sorcerer']],
            ]);
        });

        $response = $this->getJson('/api/tft-data');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'champions' => [['id', 'name']],
            'items' => [['id', 'name']],
            'traits' => [['id', 'name']],
        ]);
    }
}
