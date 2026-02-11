<?php

namespace App\Console\Commands;

use App\Models\PostDailyStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateDailyStats extends Command
{
    protected $signature = 'analytics:aggregate-daily
                            {--date= : Specific date to aggregate (Y-m-d). Defaults to yesterday.}';

    protected $description = 'Aggregate post views into daily statistics';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->toDateString()
            : now()->subDay()->toDateString();

        $this->info("Aggregating stats for: {$date}");

        $stats = DB::table('post_views')
            ->select(
                'post_uuid',
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT COALESCE(CAST(user_id AS CHAR), ip_address)) as unique_viewers')
            )
            ->whereDate('viewed_at', $date)
            ->groupBy('post_uuid')
            ->get();

        $count = 0;
        foreach ($stats as $stat) {
            PostDailyStat::updateOrCreate(
                [
                    'post_uuid' => $stat->post_uuid,
                    'date' => $date,
                ],
                [
                    'total_views' => $stat->total_views,
                    'unique_viewers' => $stat->unique_viewers,
                ]
            );
            $count++;
        }

        $this->info("Aggregated stats for {$count} posts.");

        return Command::SUCCESS;
    }
}
