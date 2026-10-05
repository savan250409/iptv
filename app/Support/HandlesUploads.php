<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores files under public/upload/video/{category}/... and keeps old
 * (storage/app/public) paths working so existing data is never broken.
 *
 *   public/upload/video/{category}/category-images/            category image
 *   public/upload/video/{category}/video/                      video file
 *   public/upload/video/{category}/video-video-thumbanailimage/ video thumbnail
 */
trait HandlesUploads
{
    /** Filesystem-safe folder name from a category's original name. */
    protected function catFolder(string $name): string
    {
        $safe = preg_replace('/[^A-Za-z0-9 _-]/', '', $name);
        $safe = trim(preg_replace('/\s+/', ' ', (string) $safe));
        $safe = str_replace(' ', '-', $safe);
        return $safe !== '' ? $safe : 'category';
    }

    /**
     * Resolve a UNIQUE folder for a new category. If the name is already taken
     * (another category or an existing folder on disk), a timestamp is appended.
     */
    protected function makeCategoryFolder(string $name): string
    {
        $base = $this->catFolder($name);
        if (!$this->folderTaken($base)) {
            return $base;
        }
        $folder = $base . '-' . time();
        while ($this->folderTaken($folder)) {
            $folder = $base . '-' . time() . mt_rand(10, 99);
        }
        return $folder;
    }

    private function folderTaken(string $folder): bool
    {
        return Category::where('folder', $folder)->exists()
            || is_dir(public_path('upload/video/' . $folder));
    }

    /** Store an uploaded file in $dir (relative to public/upload); returns only the FILE NAME. */
    protected function storeUpload(UploadedFile $file, string $dir): string
    {
        $ext  = strtolower($file->getClientOriginalExtension());
        $name = Str::random(40) . ($ext !== '' ? '.' . $ext : '');
        $file->storeAs($dir, $name, 'uploads');
        return $name;
    }

    /** Store raw binary (e.g. a canvas thumbnail) in $dir; returns only the FILE NAME. */
    protected function storeBinary(string $binary, string $dir, string $ext): string
    {
        $name = Str::random(40) . '.' . $ext;
        Storage::disk('uploads')->put(trim($dir, '/') . '/' . $name, $binary);
        return $name;
    }

    /**
     * Delete a stored value. $dir rebuilds the path for new (file-name-only) values;
     * legacy values that already contain a path are deleted as-is.
     */
    protected function removeFile(?string $stored, string $dir): void
    {
        if (!$stored) {
            return;
        }
        if (str_contains($stored, '/')) {
            $this->deleteLegacy($stored); // old full-path value
            return;
        }
        $relative = trim($dir, '/') . '/' . $stored;
        Storage::disk('uploads')->delete($relative);
        $this->removeEmptyDirs(dirname($relative));
    }

    /** Delete a legacy full-path value (upload/... or storage disk path). */
    protected function deleteLegacy(string $path): void
    {
        if (str_starts_with($path, 'upload/')) {
            $relative = substr($path, 7);
            Storage::disk('uploads')->delete($relative);
            $this->removeEmptyDirs(dirname($relative));
        } else {
            Storage::disk('public')->delete($path);
        }
    }

    /** Walk upward from a folder and delete it (and its now-empty parents) while empty. */
    protected function removeEmptyDirs(string $relativeDir): void
    {
        $root = rtrim(str_replace('\\', '/', public_path('upload')), '/');
        $dir  = $root . '/' . trim(str_replace('\\', '/', $relativeDir), '/');

        while (is_dir($dir)) {
            $real = str_replace('\\', '/', realpath($dir) ?: '');
            if ($real === '' || $real === $root || !str_starts_with($real, $root . '/')) {
                break; // never go above public/upload
            }
            $entries = array_diff(scandir($dir) ?: [], ['.', '..']);
            if (!empty($entries)) {
                break; // folder still has content
            }
            @rmdir($dir);
            $dir = dirname($dir);
        }
    }

    /** Remove a category's whole upload footprint (video files/images + thumbnails). */
    protected function removeCategoryFolder(?string $folder): void
    {
        if (!$folder) {
            return;
        }
        // video files + category images live under video/{folder}
        if (is_dir(public_path('upload/video/' . $folder))) {
            Storage::disk('uploads')->deleteDirectory('video/' . $folder);
            $this->removeEmptyDirs('video');
        }
        // thumbnails live under {folder}
        if (is_dir(public_path('upload/' . $folder))) {
            Storage::disk('uploads')->deleteDirectory($folder);
        }
    }
}
