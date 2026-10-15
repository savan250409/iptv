<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use App\Support\DataCache;
use App\Support\HandlesUploads;
use App\Support\Upload;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    use HandlesUploads;

    /** AJAX → JSON {redirect} (+ flashed toast); normal → redirect back or to return_url. */
    private function saved(Request $request, string $message, ?string $defaultRedirect = null)
    {
        $redirect = $this->safeRedirectUrl($request->input('return_url'), $defaultRedirect ?: url()->previous());
        if ($request->expectsJson()) {
            $request->session()->flash('status', $message);
            return response()->json(['redirect' => $redirect]);
        }
        return redirect($redirect)->with('status', $message);
    }

    private function safeRedirectUrl(?string $url, string $fallback): string
    {
        if (!$url) {
            return $fallback;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }
        $parsed = parse_url($url);
        if (!empty($parsed['host']) && $parsed['host'] === request()->getHost()) {
            return $url;
        }
        return $fallback;
    }

    private array $rules = [
        'category_id' => ['required', 'exists:categories,id'],
        'name'        => ['required', 'string', 'max:190'],
    ];

    private array $imageRule = ['nullable', 'mimes:webp', 'mimetypes:image/webp', 'max:5120'];

    private array $messages = [
        'image.mimes'     => 'The sub-category image must be a WebP (.webp) file.',
        'image.mimetypes' => 'The sub-category image must be a WebP (.webp) file.',
    ];

    /** Screen — sub-categories list (searchable, filterable, paginated). */
    public function index(Request $request)
    {
        $search    = trim((string) $request->query('q', ''));
        $filterCat = (int) $request->query('category_id', 0);
        $perPage   = (int) $request->query('per_page', 10);
        $perPage   = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = SubCategory::with('category:id,name,folder')
            ->withCount('videos')
            ->orderByDesc('id');

        if ($filterCat) {
            $query->where('category_id', $filterCat);
        }
        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $subCategories = $query->paginate($perPage)->appends($request->query());

        if ($subCategories->currentPage() > 1 && $subCategories->isEmpty()) {
            $lastPage = max(1, $subCategories->lastPage());
            $params = $request->query();
            if ($lastPage > 1) {
                $params['page'] = $lastPage;
            } else {
                unset($params['page']);
            }
            return redirect()->route('sub-categories.index', $params);
        }

        $allCategories = Category::orderBy('name')->get(['id', 'name']);

        return view('sub-categories.index', compact('subCategories', 'allCategories', 'search', 'filterCat', 'perPage'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules + ['image' => $this->imageRule], $this->messages);

        $category = Category::find($data['category_id']);

        $sub = new SubCategory();
        $sub->category_id = $data['category_id'];
        $sub->name = $data['name'];
        $sub->is_active = $request->boolean('is_active');
        $sub->sort_order = 0;
        if ($request->hasFile('image')) {
            $sub->image = $this->storeUpload($request->file('image'), Upload::subCategoryImageDir($category->folder));
        }
        $sub->save();
        DataCache::bump();

        return $this->saved($request, 'Sub-category added.', route('sub-categories.index'));
    }

    public function update(Request $request, SubCategory $subCategory)
    {
        $data = $request->validate($this->rules + ['image' => $this->imageRule], $this->messages);

        $category = Category::find($data['category_id']);
        $dir = Upload::subCategoryImageDir($category->folder);

        $subCategory->category_id = $data['category_id'];
        $subCategory->name = $data['name'];
        $subCategory->is_active = $request->boolean('is_active');
        if ($request->hasFile('image')) {
            $this->removeFile($subCategory->image, $dir);
            $subCategory->image = $this->storeUpload($request->file('image'), $dir);
        }
        $subCategory->save();
        DataCache::bump();

        return $this->saved($request, 'Sub-category updated.');
    }

    public function destroy(Request $request, SubCategory $subCategory)
    {
        $this->removeFile($subCategory->image, Upload::subCategoryImageDir(optional($subCategory->category)->folder));
        $subCategory->delete(); // videos' sub_category_id set to null via FK
        DataCache::bump();

        $redirect = $this->safeRedirectUrl($request->input('return_url'), url()->previous());
        return redirect($redirect)->with('status', 'Sub-category deleted.');
    }

    /** JSON list of a category's sub-categories (for the video form dropdown). */
    public function byCategory(Request $request)
    {
        $categoryId = (int) $request->query('category_id', 0);
        $subs = SubCategory::where('category_id', $categoryId)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json(['data' => $subs]);
    }
}
