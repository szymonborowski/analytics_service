<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InternalApiKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function request_without_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/v1/trending');

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);
    }

    #[Test]
    public function request_with_invalid_api_key_returns_401(): void
    {
        $response = $this->getJson('/api/v1/trending', [
            'X-Internal-Api-Key' => 'wrong-key',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function request_with_valid_api_key_passes(): void
    {
        config(['services.internal.api_key' => 'test-key']);

        $response = $this->getJson('/api/v1/trending', [
            'X-Internal-Api-Key' => 'test-key',
        ]);

        $response->assertOk();
    }
}
