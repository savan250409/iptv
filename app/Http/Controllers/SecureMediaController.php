<?php

namespace App\Http\Controllers;

use App\Support\SecureMediaHelper;
use Illuminate\Http\Request;

class SecureMediaController extends Controller
{
    private array $mimes = [
        'webp' => 'image/webp', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'bmp' => 'image/bmp',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime',
        'mkv' => 'video/x-matroska', 'm4v' => 'video/x-m4v', 'ogg' => 'video/ogg',
    ];

    /**
     * Stream a tokenised media file. The 'auth' middleware already requires a logged-in
     * admin; the token is decrypted + validated here. Supports HTTP range (video seeking).
     */
    public function stream(Request $request)
    {
        $path = SecureMediaHelper::resolve($request->query('token'));
        if (!$path) {
            abort(403); // expired, tampered, traversal, or missing
        }

        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $this->mimes[$ext] ?? 'application/octet-stream';

        $response = response()->file($path); // BinaryFileResponse handles Range requests
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Disposition', 'inline');
        return $response;
    }
}
