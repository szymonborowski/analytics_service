<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostDailyStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_uuid',
        'date',
        'total_views',
        'unique_viewers',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
