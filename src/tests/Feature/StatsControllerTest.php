<?php

namespace Tests\Feature;

use App\Models\PostDailyStat;
use App\Models\PostView;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatsControllerTest extends TestCase
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

    // --- postStats ---

    #[Test]
    public function post_stats_returns_aggregated_data(): void
    {
        $uuid = 'aaaaaaaa-1111-2222-3333-444444444444';

        PostDailyStat::create([
            'post_uuid' => $uuid,
            'date' => Carbon::now()->subDays(2)->toDateString(),
            'total_views' => 10,
            'unique_viewers' => 5,
        ]);
        PostDailyStat::create([
            'post_uuid' => $uuid,
            'date' => Carbon::now()->subDay()->toDateString(),
            'total_views' => 20,
            'unique_viewers' => 8,
        ]);

        $response = $this->getJson("/api/v1/posts/{$uuid}/stats", $this->apiHeaders());

        $response->assertOk()
            ->assertJson([
                'post_uuid' => $uuid,
                'period' => 'month',
                'total_views' => 30,
                'unique_viewers' => 13,
            ])
            ->assertJsonCount(2, 'daily_stats');
    }

    #[Test]
    public function post_stats_returns_empty_for_unknown_post(): void
    {
        $response = $this->getJson('/api/v1/posts/nonexistent-uuid/stats', $this->apiHeaders());

        $response->assertOk()
            ->assertJson([
                'total_views' => 0,
                'unique_viewers' => 0,
            ])
            ->assertJsonCount(0, 'daily_stats');
    }

    #[Test]
    public function post_stats_filters_by_period(): void
    {
        $uuid = 'bbbbbbbb-1111-2222-3333-444444444444';

        PostDailyStat::create([
            'post_uuid' => $uuid,
            'date' => Carbon::now()->subDays(3)->toDateString(),
            'total_views' => 10,
            'unique_viewers' => 5,
        ]);
        PostDailyStat::create([
            'post_uuid' => $uuid,
            'date' => Carbon::now()->subMonths(2)->toDateString(),
            'total_views' => 50,
            'unique_viewers' => 25,
        ]);

        $response = $this->getJson("/api/v1/posts/{$uuid}/stats?period=month", $this->apiHeaders());

        $response->assertOk()
            ->assertJson(['total_views' => 10, 'unique_viewers' => 5])
            ->assertJsonCount(1, 'daily_stats');
    }

    #[Test]
    public function post_stats_all_period_returns_everything(): void
    {
        $uuid = 'cccccccc-1111-2222-3333-444444444444';

        PostDailyStat::create([
            'post_uuid' => $uuid,
            'date' => Carbon::now()->subYear()->subDay()->toDateString(),
            'total_views' => 100,
            'unique_viewers' => 40,
        ]);
        PostDailyStat::create([
            'post_uuid' => $uuid,
            'date' => Carbon::now()->subDay()->toDateString(),
            'total_views' => 5,
            'unique_viewers' => 3,
        ]);

        $response = $this->getJson("/api/v1/posts/{$uuid}/stats?period=all", $this->apiHeaders());

        $response->assertOk()
            ->assertJson(['total_views' => 105, 'unique_viewers' => 43])
            ->assertJsonCount(2, 'daily_stats');
    }

    // --- authorStats ---

    #[Test]
    public function author_stats_requires_post_uuids(): void
    {
        $response = $this->getJson('/api/v1/authors/1/stats', $this->apiHeaders());

        $response->assertStatus(422)
            ->assertJson(['error' => 'post_uuids parameter required']);
    }

    #[Test]
    public function author_stats_returns_data_for_given_posts(): void
    {
        $uuid1 = 'dddddddd-1111-2222-3333-444444444444';
        $uuid2 = 'eeeeeeee-1111-2222-3333-444444444444';

        PostDailyStat::create([
            'post_uuid' => $uuid1,
            'date' => Carbon::now()->subDay()->toDateString(),
            'total_views' => 15,
            'unique_viewers' => 7,
        ]);
        PostDailyStat::create([
            'post_uuid' => $uuid2,
            'date' => Carbon::now()->subDay()->toDateString(),
            'total_views' => 25,
            'unique_viewers' => 12,
        ]);

        $response = $this->getJson(
            "/api/v1/authors/1/stats?post_uuids={$uuid1},{$uuid2}",
            $this->apiHeaders()
        );

        $response->assertOk()
            ->assertJson([
                'user_id' => 1,
                'total_views' => 40,
                'unique_viewers' => 19,
            ])
            ->assertJsonCount(2, 'top_posts');
    }

    // --- trending ---

    #[Test]
    public function trending_returns_posts_ordered_by_views(): void
    {
        $popular = 'ffffffff-1111-2222-3333-444444444444';
        $less = '11111111-1111-2222-3333-444444444444';

        PostDailyStat::create([
            'post_uuid' => $popular,
            'date' => Carbon::now()->subDay()->toDateString(),
            'total_views' => 100,
            'unique_viewers' => 50,
        ]);
        PostDailyStat::create([
            'post_uuid' => $less,
            'date' => Carbon::now()->subDay()->toDateString(),
            'total_views' => 10,
            'unique_viewers' => 5,
        ]);

        $response = $this->getJson('/api/v1/trending', $this->apiHeaders());

        $response->assertOk()
            ->assertJson(['period' => '7d'])
            ->assertJsonCount(2, 'posts');

        $posts = $response->json('posts');
        $this->assertEquals($popular, $posts[0]['post_uuid']);
        $this->assertEquals(100, $posts[0]['views']);
    }

    #[Test]
    public function trending_respects_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            PostDailyStat::create([
                'post_uuid' => "00000000-0000-0000-0000-00000000000{$i}",
                'date' => Carbon::now()->subDay()->toDateString(),
                'total_views' => 10 + $i,
                'unique_viewers' => 5,
            ]);
        }

        $response = $this->getJson('/api/v1/trending?limit=3', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(3, 'posts');
    }

    #[Test]
    public function trending_excludes_old_data(): void
    {
        PostDailyStat::create([
            'post_uuid' => '22222222-1111-2222-3333-444444444444',
            'date' => Carbon::now()->subDays(30)->toDateString(),
            'total_views' => 100,
            'unique_viewers' => 50,
        ]);

        $response = $this->getJson('/api/v1/trending?period=7d', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(0, 'posts');
    }
}
