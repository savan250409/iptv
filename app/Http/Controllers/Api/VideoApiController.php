<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Video;
use App\Support\DataCache;
use Illuminate\Http\Request;

class VideoApiController extends Controller
{
    /**
     * API #2 — Get videos by category id (POST, category_id in the body).
     * POST /api/getVideoByCategoryID   body: { "category_id": 1, "page": 1, "per_page": 20 }
     */
    public function byCategory(Request $request)
    {
        $categoryId = (int) $request->input('category_id', 0);
        if ($categoryId <= 0) {
            return response()->json(['success' => false, 'message' => 'category_id is required.'], 400);
        }

        $host = $request->getHost();

        // Cache the active-category lookup (cheap, but hit on every request).
        $category = DataCache::remember("api:cat:$categoryId:$host", 60, function () use ($categoryId) {
            $c = Category::where('id', $categoryId)->where('is_active', true)->first(['id', 'name', 'image']);
            return $c ? ['id' => $c->id, 'name' => $c->name, 'image' => $c->image_url] : null;
        });

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $page    = max(1, (int) $request->input('page', 1));

        $payload = DataCache::remember("api:vids:$categoryId:$page:$perPage:$host", 60, function () use ($categoryId, $perPage) {
            $videos = Video::query()
                ->select(['id', 'category_id', 'title', 'video_file', 'thumbnail', 'created_at'])
                ->with('category:id,folder')
                ->where('category_id', $categoryId)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->paginate($perPage);

            return [
                'data' => $videos->getCollection()->map(fn ($v) => [
                    'id'         => $v->id,
                    'title'      => $v->title,
                    'video_url'  => $v->video_url,
                    'thumbnail'  => $v->thumbnail_url,
                    'created_at' => optional($v->created_at)->toDateTimeString(),
                ])->all(),
            ];
        });

        return response()->json(['success' => true, 'category' => $category] + $payload);
    }
}
