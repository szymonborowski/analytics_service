<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikesController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'likeable_type' => 'required|in:post,comment',
            'likeable_id' => 'required|string|max:36',
            'ip_address' => 'required|string|max:45',
            'user_id' => 'nullable|integer',
        ]);

        $attributes = [
            'likeable_type' => $request->input('likeable_type'),
            'likeable_id' => $request->input('likeable_id'),
            'ip_address' => $request->input('ip_address'),
        ];

        $existing = Like::where($attributes)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            Like::create([
                ...$attributes,
                'user_id' => $request->input('user_id'),
                'created_at' => now(),
            ]);
            $liked = true;
        }

        $count = Like::where('likeable_type', $attributes['likeable_type'])
            ->where('likeable_id', $attributes['likeable_id'])
            ->count();

        return response()->json([
            'liked' => $liked,
            'count' => $count,
        ]);
    }

    public function batch(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|max:100',
            'items.*.type' => 'required|in:post,comment',
            'items.*.id' => 'required|string|max:36',
            'ip_address' => 'nullable|string|max:45',
        ]);

        $items = $request->input('items');
        $ipAddress = $request->input('ip_address');

        $results = [];

        foreach ($items as $item) {
            $type = $item['type'];
            $id = $item['id'];

            $count = Like::where('likeable_type', $type)
                ->where('likeable_id', $id)
                ->count();

            $liked = false;
            if ($ipAddress) {
                $liked = Like::where('likeable_type', $type)
                    ->where('likeable_id', $id)
                    ->where('ip_address', $ipAddress)
                    ->exists();
            }

            $results[] = [
                'type' => $type,
                'id' => $id,
                'count' => $count,
                'liked' => $liked,
            ];
        }

        return response()->json(['data' => $results]);
    }
}
