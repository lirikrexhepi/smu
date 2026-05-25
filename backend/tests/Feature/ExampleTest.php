<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_health_endpoint_returns_frontend_envelope(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => null,
                'errors' => null,
                'meta' => [],
            ])
            ->assertJsonPath('data.status', 'ok');
    }
}
