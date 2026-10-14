<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Models\Video;
use App\Support\DataCache;
use Illuminate\Http\Request;

class SubCategoryApiController extends Controller
{
    /**
     * API — Get sub-categories by category id.
     * POST /api/getsubcategorybycategoryid   body: { "category_id": 1 }
     */
    public function byCategory(Request $request)
    {
        $categoryId = (int) $request->input('category_id', 0);
        if ($categoryId <= 0) {
            return response()->json(['success' => false, 'message' => 'category_id is required.'], 400);
        }

        $host = $request->getHost();

        $data = DataCache::remember("api:subcats:$categoryId:$host", 60, function () use ($categoryId) {
            return SubCategory::query()
                ->select(['id', 'category_id', 'name', 'image'])
                ->with('category:id,folder')
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($s) {
                    // Videos of this sub-category (ascending order, limit 5).
                    $videos = Video::where('sub_category_id', $s->id)
                        ->orderBy('sort_order')
                        ->orderBy('id', 'asc')
                        ->limit(5)
                        ->get(['id', 'category_id', 'title', 'episode_number', 'video_file', 'thumbnail']);
                    $videos->each->setRelation('category', $s->category);

                    return [
                        'id'     => $s->id,
                        'name'   => $s->name,
                        'image'  => $s->image_url,
                        'videos' => $videos->map(fn ($v) => [
                            'id'             => $v->id,
                            'title'          => $v->title,
                            'episode_number' => $v->episode_number,
                            'video_url'      => $v->video_url,
                            'thumbnail'      => $v->thumbnail_url,
                        ])->all(),
                    ];
                })->all();
        });

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'No sub-categories found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
