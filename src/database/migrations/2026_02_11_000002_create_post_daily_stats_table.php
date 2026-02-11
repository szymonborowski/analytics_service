<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->char('post_uuid', 36);
            $table->date('date');
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('unique_viewers')->default(0);

            $table->unique(['post_uuid', 'date']);
            $table->index('date');
            $table->index(['post_uuid', 'total_views']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_daily_stats');
    }
};
