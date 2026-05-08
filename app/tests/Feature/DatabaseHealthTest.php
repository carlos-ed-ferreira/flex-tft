<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_health_returns_200(): void
    {
        $response = $this->getJson('/api/db-health');

        $response->assertStatus(200);
    }

    public function test_db_health_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/db-health');

        $response->assertJsonStructure(['version', 'max_connections', 'open_connections']);
    }

    public function test_db_health_version_is_valid_string(): void
    {
        $response = $this->getJson('/api/db-health');

        $version = $response->json('version');

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+/', $version);
    }

    public function test_db_health_max_connections_is_positive_integer(): void
    {
        $response = $this->getJson('/api/db-health');

        $maxConnections = $response->json('max_connections');

        $this->assertIsInt($maxConnections);
        $this->assertGreaterThan(0, $maxConnections);
    }

    public function test_db_health_open_connections_is_non_negative_integer(): void
    {
        $response = $this->getJson('/api/db-health');

        $openConnections = $response->json('open_connections');

        $this->assertIsInt($openConnections);
        $this->assertGreaterThanOrEqual(1, $openConnections);
    }
}
