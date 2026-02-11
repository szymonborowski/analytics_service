<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostDailyStat;
use App\Models\PostView;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function postStats(Request $request, string $postUuid): JsonResponse
    {
        $period = $request->query('period', 'month');
        $dateRange = $this->getDateRange($period);

        $dailyStats = PostDailyStat::where('post_uuid', $postUuid)
            ->when($dateRange, fn ($q) => $q->where('date', '>=', $dateRange))
            ->orderBy('date', 'desc')
            ->get();

        $totalViews = $dailyStats->sum('total_views');
        $uniqueViewers = $dailyStats->sum('unique_viewers');

        return response()->json([
            'post_uuid' => $postUuid,
            'period' => $period,
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'daily_stats' => $dailyStats->map(fn ($stat) => [
                'date' => $stat->date->toDateString(),
                'total_views' => $stat->total_views,
                'unique_viewers' => $stat->unique_viewers,
            ]),
        ]);
    }

    public function authorStats(Request $request, int $userId): JsonResponse
    {
        $period = $request->query('period', 'month');
        $dateRange = $this->getDateRange($period);

        // Get all post UUIDs this author has views for
        $postUuids = PostView::where('user_id', '!=', $userId)
            ->select('post_uuid')
            ->distinct()
            ->pluck('post_uuid');

        // Actually, we need to know which posts belong to the author.
        // Since we don't store author_id per post, we query by post_views
        // where the post was viewed. The caller (admin/frontend) should
        // pass post_uuids as a filter.
        $postUuids = $request->query('post_uuids');

        if (!$postUuids) {
            return response()->json([
                'user_id' => $userId,
                'error' => 'post_uuids parameter required',
            ], 422);
        }

        $uuids = is_array($postUuids) ? $postUuids : explode(',', $postUuids);

        $dailyStats = PostDailyStat::whereIn('post_uuid', $uuids)
            ->when($dateRange, fn ($q) => $q->where('date', '>=', $dateRange))
            ->get();

        $totalViews = $dailyStats->sum('total_views');
        $uniqueViewers = $dailyStats->sum('unique_viewers');

        $topPosts = PostDailyStat::whereIn('post_uuid', $uuids)
            ->when($dateRange, fn ($q) => $q->where('date', '>=', $dateRange))
            ->select('post_uuid')
            ->selectRaw('SUM(total_views) as views')
            ->groupBy('post_uuid')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        return response()->json([
            'user_id' => $userId,
            'period' => $period,
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'top_posts' => $topPosts->map(fn ($p) => [
                'post_uuid' => $p->post_uuid,
                'views' => (int) $p->views,
            ]),
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $period = $request->query('period', '7d');
        $limit = min((int) $request->query('limit', 10), 50);

        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        $since = Carbon::now()->subDays($days)->toDateString();

        $trending = PostDailyStat::where('date', '>=', $since)
            ->select('post_uuid')
            ->selectRaw('SUM(total_views) as views')
            ->selectRaw('SUM(unique_viewers) as unique_views')
            ->groupBy('post_uuid')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();

        return response()->json([
            'period' => $period,
            'posts' => $trending->map(fn ($p) => [
                'post_uuid' => $p->post_uuid,
                'views' => (int) $p->views,
                'unique_views' => (int) $p->unique_views,
            ]),
        ]);
    }

    private function getDateRange(string $period): ?string
    {
        return match ($period) {
            'day' => Carbon::now()->toDateString(),
            'week' => Carbon::now()->subWeek()->toDateString(),
            'month' => Carbon::now()->subMonth()->toDateString(),
            'year' => Carbon::now()->subYear()->toDateString(),
            'all' => null,
            default => Carbon::now()->subMonth()->toDateString(),
        };
    }
}
