<?php

namespace Tests\Feature;

use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LikesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.internal.api_key' => 'test-key']);
    }

    private function apiHeaders(): array
    {
        return ['X-Internal-Api-Key' => 'test-key'];
    }

    // --- toggle ---

    #[Test]
    public function toggle_creates_like(): void
    {
        $response = $this->postJson('/api/v1/likes/toggle', [
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '127.0.0.1',
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertDatabaseHas('likes', [
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '127.0.0.1',
        ]);
    }

    #[Test]
    public function toggle_removes_existing_like(): void
    {
        Like::create([
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/likes/toggle', [
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '127.0.0.1',
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);

        $this->assertDatabaseMissing('likes', [
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '127.0.0.1',
        ]);
    }

    #[Test]
    public function toggle_with_user_id(): void
    {
        $response = $this->postJson('/api/v1/likes/toggle', [
            'likeable_type' => 'comment',
            'likeable_id' => 'comment-42',
            'ip_address' => '10.0.0.1',
            'user_id' => 5,
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertDatabaseHas('likes', [
            'likeable_id' => 'comment-42',
            'user_id' => 5,
        ]);
    }

    #[Test]
    public function toggle_returns_correct_count_with_multiple_likes(): void
    {
        Like::create([
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '10.0.0.1',
            'created_at' => now(),
        ]);
        Like::create([
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '10.0.0.2',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/likes/toggle', [
            'likeable_type' => 'post',
            'likeable_id' => 'post-uuid-1',
            'ip_address' => '10.0.0.3',
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJson(['liked' => true, 'count' => 3]);
    }

    #[Test]
    public function toggle_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/likes/toggle', [], $this->apiHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['likeable_type', 'likeable_id', 'ip_address']);
    }

    #[Test]
    public function toggle_validates_likeable_type(): void
    {
        $response = $this->postJson('/api/v1/likes/toggle', [
            'likeable_type' => 'invalid',
            'likeable_id' => 'some-id',
            'ip_address' => '127.0.0.1',
        ], $this->apiHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['likeable_type']);
    }

    // --- batch ---

    #[Test]
    public function batch_returns_counts_and_liked_status(): void
    {
        Like::create([
            'likeable_type' => 'post',
            'likeable_id' => 'post-1',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
        Like::create([
            'likeable_type' => 'post',
            'likeable_id' => 'post-1',
            'ip_address' => '10.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/likes/batch', [
            'ip_address' => '127.0.0.1',
            'items' => [
                ['type' => 'post', 'id' => 'post-1'],
                ['type' => 'post', 'id' => 'post-2'],
            ],
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    ['type' => 'post', 'id' => 'post-1', 'count' => 2, 'liked' => true],
                    ['type' => 'post', 'id' => 'post-2', 'count' => 0, 'liked' => false],
                ],
            ]);
    }

    #[Test]
    public function batch_without_ip_returns_liked_false(): void
    {
        Like::create([
            'likeable_type' => 'post',
            'likeable_id' => 'post-1',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/likes/batch', [
            'items' => [
                ['type' => 'post', 'id' => 'post-1'],
            ],
        ], $this->apiHeaders());

        $response->assertOk()
            ->assertJson([
                'data' => [
                    ['count' => 1, 'liked' => false],
                ],
            ]);
    }

    #[Test]
    public function batch_validates_items(): void
    {
        $response = $this->postJson('/api/v1/likes/batch', [], $this->apiHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }
}
