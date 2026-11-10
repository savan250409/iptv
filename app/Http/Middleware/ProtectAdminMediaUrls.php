<?php

namespace App\Http\Middleware;

use App\Support\SecureMediaHelper;
use Closure;
use Illuminate\Http\Request;

/**
 * On admin responses (HTML + JSON), rewrites every /upload/... media URL into an
 * encrypted, expiring /secure-media?token=... URL. The real file path never reaches
 * the browser, view-source, DOM, or AJAX JSON — so media can't be bulk-scraped.
 *
 * For HTML it goes one step further: the token is handed over in a `data-ms`
 * holder attribute instead of a live `src`/`poster`, so the browser never requests
 * the token URL directly. The client (admin-media-protect.js) fetches the bytes once
 * and swaps in an in-memory blob: URL, then drops the token attribute — so nothing
 * readable (no path, no token) is ever left in the live DOM.
 *
 * Only registered on the admin web group, so the public API is never touched.
 */
class ProtectAdminMediaUrls
{
    /** Token lifetime (seconds). Auth-gated too, so this can be generous for admin UX. */
    private const TTL = 3600;

    /** Media extensions to protect. */
    private const EXT = 'webp|png|jpe?g|gif|bmp|svg|mp4|webm|mov|mkv|m4v|ogg';

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $type = strtolower((string) $response->headers->get('Content-Type', ''));
        $isHtml = str_contains($type, 'text/html');
        $isJson = str_contains($type, 'application/json');
        if (!$isHtml && !$isJson) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            return $response;
        }

        // Step 1 — turn every /upload/<path>.<ext> into an encrypted token URL.
        // Runs for HTML and JSON (JSON feeds the AJAX-rendered lists/modals).
        $pattern = '#(?:https?://[^/\s"\'<>\\\\]+)?(/upload/[^\s"\'<>\\\\]+?\.(?:' . self::EXT . '))#i';

        $content = preg_replace_callback($pattern, function ($m) {
            $relative = ltrim($m[1], '/');                    // upload/....
            $token    = SecureMediaHelper::token($relative, self::TTL);
            return url('/secure-media') . '?token=' . urlencode($token);
        }, $content);

        // Step 2 — HTML only: move the token out of live src/poster into a data-ms
        // holder, so no token is fetched directly and none sits in the rendered src.
        if ($isHtml && is_string($content)) {
            $content = preg_replace(
                '#\bsrc=(["\'])([^"\']*?/secure-media\?token=[^"\']*)\1#i',
                'data-ms=$1$2$1',
                $content
            );
            $content = preg_replace(
                '#\bposter=(["\'])([^"\']*?/secure-media\?token=[^"\']*)\1#i',
                'data-ms-poster=$1$2$1',
                $content
            );
        }

        if ($content !== null) {
            $response->setContent($content);
        }
        return $response;
    }
}
