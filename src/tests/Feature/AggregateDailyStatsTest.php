<?php

namespace Tests\Feature;

use App\Models\PostDailyStat;
use App\Models\PostView;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AggregateDailyStatsTest extends TestCase
{
    use RefreshDatabase;

    private function findStat(string $uuid, string $date): ?PostDailyStat
    {
        return PostDailyStat::where('post_uuid', $uuid)
            ->whereDate('date', $date)
            ->first();
    }

    #[Test]
    public function it_aggregates_views_into_daily_stats(): void
    {
        $uuid = 'aaaaaaaa-1111-2222-3333-444444444444';
        $yesterday = Carbon::yesterday()->toDateString();

        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.1', 'viewed_at' => "{$yesterday} 10:00:00"]);
        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.2', 'viewed_at' => "{$yesterday} 11:00:00"]);
        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.1', 'viewed_at' => "{$yesterday} 12:00:00"]);

        $this->artisan('analytics:aggregate-daily')->assertSuccessful();

        $stat = $this->findStat($uuid, $yesterday);

        $this->assertNotNull($stat);
        $this->assertEquals(3, $stat->total_views);
        $this->assertEquals(2, $stat->unique_viewers);
    }

    #[Test]
    public function it_aggregates_for_specific_date(): void
    {
        $uuid = 'bbbbbbbb-1111-2222-3333-444444444444';
        $date = '2026-01-15';

        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.1', 'viewed_at' => "{$date} 08:00:00"]);

        $this->artisan("analytics:aggregate-daily --date={$date}")->assertSuccessful();

        $stat = $this->findStat($uuid, $date);

        $this->assertNotNull($stat);
        $this->assertEquals($date, $stat->date->toDateString());
        $this->assertEquals(1, $stat->total_views);
        $this->assertEquals(1, $stat->unique_viewers);
    }

    #[Test]
    public function it_upserts_existing_stats(): void
    {
        $uuid = 'cccccccc-1111-2222-3333-444444444444';
        $yesterday = Carbon::yesterday();

        PostDailyStat::create([
            'post_uuid' => $uuid,
            'date' => $yesterday->startOfDay(),
            'total_views' => 5,
            'unique_viewers' => 3,
        ]);

        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.1', 'viewed_at' => $yesterday->toDateString() . ' 10:00:00']);
        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.2', 'viewed_at' => $yesterday->toDateString() . ' 11:00:00']);

        $this->artisan('analytics:aggregate-daily')->assertSuccessful();

        $stat = $this->findStat($uuid, $yesterday->toDateString());

        $this->assertNotNull($stat);
        $this->assertEquals(2, $stat->total_views);
        $this->assertEquals(2, $stat->unique_viewers);
        $this->assertEquals(1, PostDailyStat::where('post_uuid', $uuid)->count());
    }

    #[Test]
    public function it_aggregates_multiple_posts_separately(): void
    {
        $uuid1 = 'dddddddd-1111-2222-3333-444444444444';
        $uuid2 = 'eeeeeeee-1111-2222-3333-444444444444';
        $yesterday = Carbon::yesterday()->toDateString();

        PostView::create(['post_uuid' => $uuid1, 'ip_address' => '10.0.0.1', 'viewed_at' => "{$yesterday} 10:00:00"]);
        PostView::create(['post_uuid' => $uuid1, 'ip_address' => '10.0.0.2', 'viewed_at' => "{$yesterday} 11:00:00"]);
        PostView::create(['post_uuid' => $uuid2, 'ip_address' => '10.0.0.1', 'viewed_at' => "{$yesterday} 10:00:00"]);

        $this->artisan('analytics:aggregate-daily')->assertSuccessful();

        $stat1 = $this->findStat($uuid1, $yesterday);
        $stat2 = $this->findStat($uuid2, $yesterday);

        $this->assertNotNull($stat1);
        $this->assertNotNull($stat2);
        $this->assertEquals(2, $stat1->total_views);
        $this->assertEquals(1, $stat2->total_views);
    }

    #[Test]
    public function it_ignores_views_from_other_dates(): void
    {
        $uuid = 'ffffffff-1111-2222-3333-444444444444';
        $yesterday = Carbon::yesterday()->toDateString();
        $twoDaysAgo = Carbon::now()->subDays(2)->toDateString();

        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.1', 'viewed_at' => "{$yesterday} 10:00:00"]);
        PostView::create(['post_uuid' => $uuid, 'ip_address' => '10.0.0.2', 'viewed_at' => "{$twoDaysAgo} 10:00:00"]);

        $this->artisan('analytics:aggregate-daily')->assertSuccessful();

        $stat = $this->findStat($uuid, $yesterday);

        $this->assertNotNull($stat);
        $this->assertEquals(1, $stat->total_views);
        $this->assertNull($this->findStat($uuid, $twoDaysAgo));
    }

    #[Test]
    public function it_counts_user_id_as_unique_viewer(): void
    {
        $uuid = '11111111-1111-2222-3333-444444444444';
        $yesterday = Carbon::yesterday()->toDateString();

        PostView::create(['post_uuid' => $uuid, 'user_id' => 1, 'ip_address' => '10.0.0.1', 'viewed_at' => "{$yesterday} 10:00:00"]);
        PostView::create(['post_uuid' => $uuid, 'user_id' => 1, 'ip_address' => '10.0.0.2', 'viewed_at' => "{$yesterday} 11:00:00"]);
        PostView::create(['post_uuid' => $uuid, 'user_id' => null, 'ip_address' => '10.0.0.3', 'viewed_at' => "{$yesterday} 12:00:00"]);

        $this->artisan('analytics:aggregate-daily')->assertSuccessful();

        $stat = $this->findStat($uuid, $yesterday);

        $this->assertNotNull($stat);
        $this->assertEquals(3, $stat->total_views);
        $this->assertEquals(2, $stat->unique_viewers);
    }
}
