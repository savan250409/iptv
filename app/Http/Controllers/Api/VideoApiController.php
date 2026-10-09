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
                ->select(['id', 'category_id', 'title', 'episode_number', 'video_file', 'thumbnail'])
                ->with('category:id,folder')
                ->where('category_id', $categoryId)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->paginate($perPage);

            return [
                'data' => $videos->getCollection()->map(fn ($v) => [
                    'id'             => $v->id,
                    'title'          => $v->title,
                    'episode_number' => $v->episode_number,
                    'video_url'      => $v->video_url,
                    'thumbnail'      => $v->thumbnail_url,
                ])->all(),
            ];
        });

        return response()->json(['success' => true, 'category' => $category] + $payload);
    }

    /**
     * API #3 — Mixed feed (paginated): all categories' episode 1, then all
     * categories' episode 2, and so on. Page size from .env (RANDOM_VIDEO_PER_PAGE).
     * POST /api/getrendomvideo   body: { "page": 1 }
     */
    public function getRandom(Request $request)
    {
        $perPage = max(1, min(100, (int) config('app.random_video_per_page', 10)));
        $page    = max(1, (int) $request->input('page', 1));
        $host    = $request->getHost();

        $payload = DataCache::remember("api:random:$host:$perPage:$page", 60, function () use ($perPage, $page) {
            $videos = Video::query()
                ->select(['id', 'category_id', 'title', 'episode_number', 'video_file', 'thumbnail'])
                ->with('category:id,name,folder')
                // episode 1 of every category first, then episode 2 of every category, ...
                ->orderByRaw('CAST(episode_number AS UNSIGNED) ASC')
                ->orderBy('category_id')
                ->orderBy('id')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'pagination' => [
                    'page'        => $videos->currentPage(),
                    'per_page'    => $videos->perPage(),
                    'total'       => $videos->total(),
                    'total_pages' => $videos->lastPage(),
                    'has_next'    => $videos->hasMorePages(),
                    'has_prev'    => $videos->currentPage() > 1,
                ],
                'data' => $videos->getCollection()->map(fn ($v) => [
                    'id'             => $v->id,
                    'title'          => $v->title,
                    'episode_number' => $v->episode_number,
                    'category_id'    => $v->category_id,
                    'category'       => optional($v->category)->name,
                    'video_url'      => $v->video_url,
                    'thumbnail'      => $v->thumbnail_url,
                ])->all(),
            ];
        });

        if (empty($payload['data'])) {
            return response()->json(['success' => false, 'message' => 'No videos found.'], 404);
        }

        return response()->json(['success' => true] + $payload);
    }
}
