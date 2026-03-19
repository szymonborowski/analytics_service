<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->string('likeable_type', 10); // 'post' or 'comment'
            $table->string('likeable_id', 36);   // post UUID or comment ID
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at');

            $table->unique(['likeable_type', 'likeable_id', 'user_id']);
            $table->index(['likeable_type', 'likeable_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
