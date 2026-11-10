<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Turns a public-relative media path (e.g. "upload/video/Action/category-images/x.webp")
 * into an encrypted, expiring token — and resolves it back to a real file path, with
 * tamper / expiry / path-traversal checks. The real path never appears in the page.
 */
class SecureMediaHelper
{
    /** Encrypt {path, expiry} into a URL token. */
    public static function token(string $relativePath, int $ttlSeconds): string
    {
        $payload = [
            'p' => ltrim(str_replace('\\', '/', $relativePath), '/'),
            'e' => time() + $ttlSeconds,
        ];
        return Crypt::encryptString(json_encode($payload));
    }

    /** Decrypt a token → absolute file path inside public/upload, or null if invalid. */
    public static function resolve(?string $token): ?string
    {
        if (!$token) {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return null; // tampered / not ours
        }
        if (!is_array($data) || empty($data['p']) || empty($data['e'])) {
            return null;
        }
        if (time() > (int) $data['e']) {
            return null; // expired
        }

        $rel = str_replace('\\', '/', (string) $data['p']);
        if (str_contains($rel, '..') || !str_starts_with($rel, 'upload/')) {
            return null; // traversal / outside upload
        }

        $real = realpath(public_path($rel));
        $root = realpath(public_path('upload'));
        if ($real === false || $root === false) {
            return null;
        }
        $real = str_replace('\\', '/', $real);
        $root = str_replace('\\', '/', $root);
        if ($real !== $root && !str_starts_with($real, $root . '/')) {
            return null; // outside the upload root
        }
        return is_file($real) ? $real : null;
    }
}
