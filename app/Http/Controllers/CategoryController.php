<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Video;
use App\Support\DataCache;
use App\Support\HandlesUploads;
use App\Support\Upload;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use HandlesUploads;

    /** AJAX → JSON {redirect} (+ flashed toast); normal → redirect back. */
    private function saved(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            $request->session()->flash('status', $message);
            return response()->json(['redirect' => url()->previous()]);
        }
        return back()->with('status', $message);
    }

    /** Screen 1 — categories list (searchable, filterable, paginated). */
    public function index(Request $request)
    {
        $search  = trim((string) $request->query('q', ''));
        $status  = $request->query('status', '');          // '', 'active', 'inactive'
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Category::withCount('videos')
            ->orderBy('sort_order')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $categories = $query->paginate($perPage)->appends($request->query());

        return view('categories.index', compact('categories', 'search', 'status', 'perPage'));
    }

    /** JSON list for the drag-and-drop indexing modal (loaded lazily, capped). */
    public function indexList()
    {
        $limit = 500;
        $data  = Category::orderBy('sort_order')->orderByDesc('id')->limit($limit)->get(['id', 'name']);
        $total = DataCache::remember('cat:count', 60, fn () => Category::count());

        return response()->json(['data' => $data, 'total' => $total, 'limit' => $limit]);
    }

    /** Save a new category order from the indexing modal (AJAX). */
    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            return response()->json(['success' => false], 422);
        }
        foreach (array_values($ids) as $position => $id) {
            Category::where('id', (int) $id)->update(['sort_order' => $position + 1]);
        }
        DataCache::bump();
        return response()->json(['success' => true]);
    }

    /** Flip a category's active status (AJAX, inline toggle). */
    public function toggle(Category $category)
    {
        $category->is_active = !$category->is_active;
        $category->save();
        DataCache::bump();
        return response()->json(['success' => true, 'is_active' => $category->is_active]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:190'],
            'image' => ['nullable', 'mimes:webp', 'mimetypes:image/webp', 'max:5120'], // WebP only, 5 MB
        ], [
            'image.mimes'     => 'The category image must be a WebP (.webp) file.',
            'image.mimetypes' => 'The category image must be a WebP (.webp) file.',
        ]);

        $category = new Category();
        $category->name = $data['name'];
        $category->is_active = $request->boolean('is_active');
        $category->sort_order = 0; // new categories show first (until manually indexed)
        $category->folder = $this->makeCategoryFolder($data['name']); // unique (timestamp on collision)
        if ($request->hasFile('image')) {
            $category->image = $this->storeUpload($request->file('image'), Upload::categoryImageDir($category->folder));
        }
        $category->save();
        DataCache::bump();

        return $this->saved($request, 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:190'],
            'image' => ['nullable', 'mimes:webp', 'mimetypes:image/webp', 'max:5120'],
        ], [
            'image.mimes'     => 'The category image must be a WebP (.webp) file.',
            'image.mimetypes' => 'The category image must be a WebP (.webp) file.',
        ]);

        $category->name = $data['name'];
        $category->is_active = $request->boolean('is_active');
        // Keep the category's existing folder stable (don't move files on rename).
        if (!$category->folder) {
            $category->folder = $this->makeCategoryFolder($data['name']);
        }
        if ($request->hasFile('image')) {
            $dir = Upload::categoryImageDir($category->folder);
            $this->removeFile($category->image, $dir); // old image removed + empty folder cleaned
            $category->image = $this->storeUpload($request->file('image'), $dir);
        }
        $category->save();
        DataCache::bump();

        return $this->saved($request, 'Category updated.');
    }

    public function destroy(Category $category)
    {
        // Remove image + each video file & thumbnail, then the whole category folder.
        $this->removeFile($category->image, Upload::categoryImageDir($category->folder));
        foreach ($category->videos as $video) {
            $this->removeFile($video->video_file, Upload::videoDir($category->folder));
            $this->removeFile($video->thumbnail, Upload::thumbDir($category->folder));
        }
        $this->removeCategoryFolder($category->folder);
        $category->delete();
        DataCache::bump();

        return back()->with('status', 'Category deleted (its videos were removed too).');
    }
}
