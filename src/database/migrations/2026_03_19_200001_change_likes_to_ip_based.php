<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropUnique(['likeable_type', 'likeable_id', 'user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');

            $table->string('ip_address', 45)->after('likeable_id');

            $table->unique(['likeable_type', 'likeable_id', 'ip_address']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropUnique(['likeable_type', 'likeable_id', 'ip_address']);
            $table->dropIndex(['ip_address']);
            $table->dropColumn('ip_address');

            $table->unsignedBigInteger('user_id')->after('likeable_id');

            $table->unique(['likeable_type', 'likeable_id', 'user_id']);
            $table->index('user_id');
        });
    }
};
