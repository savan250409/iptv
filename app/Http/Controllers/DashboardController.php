<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use App\Support\DataCache;

class DashboardController extends Controller
{
    /** Landing screen — overview of the Drama content. */
    public function index()
    {
        // COUNT(*) on huge tables is costly — cache the headline numbers briefly.
        $stats = DataCache::remember('dash:stats', 60, function () {
            $top = Category::withCount('videos')->orderByDesc('videos_count')->first(['id', 'name']);
            return [
                'categoryCount' => Category::count(),
                'videoCount'    => Video::count(),
                'topName'       => $top && $top->videos_count ? $top->name : null,
                'topCount'      => $top ? (int) $top->videos_count : 0,
            ];
        });

        $categoryCount = $stats['categoryCount'];
        $videoCount    = $stats['videoCount'];
        $topCategory   = (object) ['name' => $stats['topName'], 'videos_count' => $stats['topCount']];

        $recentCategories = Category::withCount('videos')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'image', 'folder', 'created_at']);

        $recentVideos = Video::with('category:id,name,folder')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'category_id', 'title', 'video_file', 'thumbnail', 'created_at']);

        return view('dashboard', compact(
            'categoryCount',
            'videoCount',
            'topCategory',
            'recentCategories',
            'recentVideos'
        ));
    }
}
