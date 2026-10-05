<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Video;
use App\Support\DataCache;
use Illuminate\Http\Request;

class CategoryApiController extends Controller
{
    /**
     * API #1 — Get categories (each with its last 5 videos), paginated + cached.
     * GET /api/get-category?page=1&per_page=20&search=news
     */
    public function index(Request $request)
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $page    = max(1, (int) $request->query('page', 1));
        $search  = trim((string) $request->query('search', ''));

        // Cache the fully-built response for a short window. The key includes the
        // request host (absolute URLs depend on it) and a version that bumps on any edit.
        $key = 'api:cats:' . $request->getHost() . ":$page:$perPage:" . md5($search);

        $payload = DataCache::remember($key, 60, function () use ($perPage, $search) {
            // Newly added categories (sort_order 0) show first; a saved index sequence follows.
            // Only categories that actually contain at least one video are returned.
            $query = Category::query()
                ->select(['id', 'name', 'image', 'folder', 'created_at'])
                ->withCount('videos')
                ->where('is_active', true)
                ->whereHas('videos')
                ->orderBy('sort_order')
                ->orderByDesc('id');

            if ($search !== '') {
                $query->where('name', 'like', $search . '%'); // prefix match → uses the name index
            }

            $categories = $query->paginate($perPage);

            return [
                'data' => $categories->getCollection()->map(function ($c) {
                    // Last 5 videos of this category (indexed order, newest first).
                    $videos = Video::where('category_id', $c->id)
                        ->orderBy('sort_order')
                        ->orderByDesc('id')
                        ->limit(5)
                        ->get(['id', 'category_id', 'title', 'video_file', 'thumbnail', 'created_at']);
                    // Reuse the parent category (already has `folder`) so URLs rebuild without extra queries.
                    $videos->each->setRelation('category', $c);

                    return [
                        'id'          => $c->id,
                        'name'        => $c->name,
                        'image'       => $c->image_url,
                        'video_count' => $c->videos_count,
                        'created_at'  => optional($c->created_at)->toDateTimeString(),
                        'videos'      => $videos->map(fn ($v) => [
                            'id'         => $v->id,
                            'title'      => $v->title,
                            'video_url'  => $v->video_url,
                            'thumbnail'  => $v->thumbnail_url,
                            'created_at' => optional($v->created_at)->toDateTimeString(),
                        ])->all(),
                    ];
                })->all(),
            ];
        });

        // No categories → don't return the normal data response.
        if (empty($payload['data'])) {
            return response()->json(['success' => false, 'message' => 'No categories found.'], 404);
        }

        return response()->json(['success' => true] + $payload);
    }
}
