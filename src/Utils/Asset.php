<?php

namespace NoteNest\Utils;

/**
 * Cache-busting URLs for static assets.
 *
 * Assets are served with far-future caching in most deployments, so a plain
 * "/assets/js/app.js" means returning users keep running whatever version their
 * browser cached - long after a deploy has shipped a fix. Appending the file's
 * modification time gives every build a distinct URL without any manifest to
 * maintain.
 */
final class Asset
{
    /** @var array<string, string> Resolved URLs, memoised per request. */
    private static array $cache = [];

    /**
     * @param string $path Root-relative asset path, e.g. "/assets/js/app.js".
     */
    public static function url(string $path): string
    {
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $file = dirname(__DIR__, 2) . '/public' . $path;
        // A missing file is not fatal: fall back to the unversioned URL and let
        // the 404 surface in the network tab rather than breaking the page.
        $version = is_file($file) ? (string) filemtime($file) : '1';

        return self::$cache[$path] = $path . '?v=' . $version;
    }
}
