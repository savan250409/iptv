<?php

namespace App\Support;

/**
 * Builds the on-disk directories (relative to public/upload) for each upload
 * type. The DATABASE stores only the file name; full paths/URLs are rebuilt
 * from the category's folder + these directories.
 *
 *   category image  → upload/video/{folder}/category-images/{file}
 *   video file      → upload/video/{folder}/video/{file}
 *   video thumbnail → upload/{folder}/video-thumabanil-image/{file}
 */
class Upload
{
    public static function categoryImageDir(?string $folder): string
    {
        return 'video/' . $folder . '/category-images';
    }

    public static function videoDir(?string $folder): string
    {
        return 'video/' . $folder . '/video';
    }

    public static function thumbDir(?string $folder): string
    {
        return $folder . '/video-thumabanil-image';
    }

    /** Public URL for a stored file name inside a given directory. */
    public static function url(string $dir, string $fileName): string
    {
        return asset('upload/' . trim($dir, '/') . '/' . $fileName);
    }
}
