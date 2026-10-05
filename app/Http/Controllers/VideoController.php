<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use App\Support\DataCache;
use App\Support\HandlesUploads;
use App\Support\Upload;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    use HandlesUploads;

    private array $videoRules = [
        'category_id' => ['required', 'exists:categories,id'],
        'title'       => ['required', 'string', 'max:190'],
    ];

    private string $videoMimes = 'video/mp4,video/webm,video/ogg,video/quicktime,video/x-matroska';

    /** List videos — searchable, filterable, paginated (like Categories). */
    public function index(Request $request)
    {
        $allCategories = Category::orderBy('name')->get();
        $filterCat     = (int) $request->query('category_id', 0);
        $search        = trim((string) $request->query('q', ''));
        $perPage       = (int) $request->query('per_page', 10);
        $perPage       = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Video::query()
            ->select(['id', 'category_id', 'title', 'video_file', 'thumbnail', 'created_at'])
            ->with('category:id,name,folder');

        if ($filterCat) {
            // Within a single category, show the saved index order.
            $query->where('category_id', $filterCat)->orderBy('sort_order')->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }
        if ($search !== '') {
            $query->where('title', 'like', '%' . $search . '%');
        }
        $videos = $query->paginate($perPage)->appends($request->query());

        return view('videos.index', compact('allCategories', 'videos', 'filterCat', 'search', 'perPage'));
    }

    /** JSON list of a category's videos for the indexing popup (capped). */
    public function reorderList(Request $request)
    {
        $categoryId = (int) $request->query('category_id', 0);
        if ($categoryId <= 0) {
            return response()->json(['data' => [], 'total' => 0, 'limit' => 0]);
        }
        $limit = 500;
        $data  = Video::where('category_id', $categoryId)
            ->with('category:id,folder')
            ->orderBy('sort_order')->orderByDesc('id')
            ->limit($limit)->get(['id', 'category_id', 'title', 'video_file', 'thumbnail'])
            ->map(fn ($v) => [
                'id'        => $v->id,
                'title'     => $v->title,
                'video_url' => $v->video_url,
                'thumbnail' => $v->thumbnail_url,
            ]);
        $total = Video::where('category_id', $categoryId)->count();

        return response()->json(['data' => $data, 'total' => $total, 'limit' => $limit]);
    }

    /** Save a new video order within a category (AJAX). */
    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            return response()->json(['success' => false], 422);
        }
        foreach (array_values($ids) as $position => $id) {
            Video::where('id', (int) $id)->update(['sort_order' => $position + 1]);
        }
        DataCache::bump();
        return response()->json(['success' => true]);
    }

    /** Add-video screen. */
    public function create()
    {
        $allCategories = Category::orderBy('name')->get();
        return view('videos.form', [
            'video'         => new Video(),
            'allCategories' => $allCategories,
            'mode'          => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->videoRules + [
            'video' => ['required', 'file', 'mimetypes:' . $this->videoMimes, 'max:512000'], // 500 MB
        ]);

        $category = Category::find($data['category_id']);
        $folder = $category->folder ?: $this->catFolder($category->name);

        Video::create([
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'video_file'  => $this->storeUpload($request->file('video'), Upload::videoDir($folder)),
            'thumbnail'   => $this->saveThumbnail($request->input('thumbnail_data'), $folder),
        ]);
        DataCache::bump();

        return redirect()->route('videos.index')->with('status', 'Video added.');
    }

    /** Edit-video screen. */
    public function edit(Video $video)
    {
        $allCategories = Category::orderBy('name')->get();
        return view('videos.form', [
            'video'         => $video,
            'allCategories' => $allCategories,
            'mode'          => 'edit',
        ]);
    }

    public function update(Request $request, Video $video)
    {
        $data = $request->validate($this->videoRules + [
            'video' => ['nullable', 'file', 'mimetypes:' . $this->videoMimes, 'max:512000'],
        ]);

        $video->category_id = $data['category_id'];
        $video->title       = $data['title'];

        $category = Category::find($data['category_id']);
        $folder = $category->folder ?: $this->catFolder($category->name);

        if ($request->hasFile('video')) {
            $this->removeFile($video->video_file, Upload::videoDir($folder));
            $video->video_file = $this->storeUpload($request->file('video'), Upload::videoDir($folder));
        }

        // Replace the thumbnail only when a fresh one was generated (new file chosen).
        if ($request->filled('thumbnail_data')) {
            $new = $this->saveThumbnail($request->input('thumbnail_data'), $folder);
            if ($new) {
                $this->removeFile($video->thumbnail, Upload::thumbDir($folder));
                $video->thumbnail = $new;
            }
        }

        $video->save();
        DataCache::bump();

        return redirect()->route('videos.index')->with('status', 'Video updated.');
    }

    /**
     * Decode a canvas-generated data URL (data:image/jpeg;base64,...) and store it.
     * Returns the stored path, or null if nothing valid was supplied.
     */
    private function saveThumbnail(?string $dataUrl, string $folder): ?string
    {
        if (!$dataUrl || !preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#', $dataUrl, $m)) {
            return null;
        }
        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) === 0 || strlen($binary) > 2 * 1024 * 1024) {
            return null; // invalid or larger than 2 MB — ignore
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        return $this->storeBinary($binary, Upload::thumbDir($folder), $ext);
    }

    public function destroy(Request $request, Video $video)
    {
        $folder = optional($video->category)->folder ?: $this->catFolder(optional($video->category)->name ?? '');
        $this->removeFile($video->video_file, Upload::videoDir($folder));
        $this->removeFile($video->thumbnail, Upload::thumbDir($folder));
        $catId = $video->category_id;
        $video->delete();
        DataCache::bump();

        return redirect()
            ->route('videos.index', $request->filled('keep_filter') ? ['category_id' => $catId] : [])
            ->with('status', 'Video deleted.');
    }
}
