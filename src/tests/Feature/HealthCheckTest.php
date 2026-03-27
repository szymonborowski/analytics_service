<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    #[Test]
    public function health_returns_ok(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    #[Test]
    public function ready_returns_ready_when_db_is_up(): void
    {
        $response = $this->getJson('/ready');

        $response->assertOk()
            ->assertJson(['status' => 'ready']);
    }

    #[Test]
    public function root_returns_ok(): void
    {
        $response = $this->getJson('/');

        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}
