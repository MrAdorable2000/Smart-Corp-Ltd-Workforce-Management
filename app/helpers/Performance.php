<?php
/**
 * Performance Helper
 * - File-based caching for expensive queries
 * - Asset versioning for cache busting
 * - Preconnect hints for faster CDN loads
 */
class Performance
{
    private static $cacheDir = STORAGE_PATH . '/cache';

    /**
     * Get cached value or compute and store it
     */
    public static function remember($key, $ttl, $callback)
    {
        $file = self::$cacheDir . '/' . md5($key) . '.json';
        if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
            $data = json_decode(file_get_contents($file), true);
            if ($data !== null) return $data;
        }
        $data = $callback();
        if (!is_dir(self::$cacheDir)) mkdir(self::$cacheDir, 0755, true);
        file_put_contents($file, json_encode($data));
        return $data;
    }

    /**
     * Forget a cached key
     */
    public static function forget($key)
    {
        $file = self::$cacheDir . '/' . md5($key) . '.json';
        if (file_exists($file)) unlink($file);
    }

    /**
     * Clear entire cache
     */
    public static function clearCache()
    {
        $files = glob(self::$cacheDir . '/*.json');
        foreach ($files as $f) unlink($f);
    }

    /**
     * Asset URL with version param for cache busting
     */
    public static function asset($path)
    {
        $fullPath = PUBLIC_PATH . '/assets/' . ltrim($path, '/');
        $version = file_exists($fullPath) ? filemtime($fullPath) : APP_VERSION;
        return ASSET_URL . '/' . ltrim($path, '/') . '?v=' . $version;
    }

    /**
     * Output preconnect headers for faster CDN loading
     */
    public static function preconnects()
    {
        $domains = [
            'https://cdn.jsdelivr.net',
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://cdn.jsdelivr.net',
        ];
        $html = '';
        foreach (array_unique($domains) as $d) {
            $html .= '<link rel="preconnect" href="' . $d . '" crossorigin>';
        }
        return $html;
    }

    /**
     * Send cache-control headers
     */
    public static function setCacheHeaders($ttl = 3600)
    {
        header("Cache-Control: public, max-age={$ttl}");
        header('Pragma: cache');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $ttl) . ' GMT');
    }

    /**
     * Send no-cache headers
     */
    public static function setNoCacheHeaders()
    {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    /**
     * Bust the dashboard AI insights cache so changes show immediately
     * Call this after ANY insert/update/delete in controllers
     */
    public static function bustDashboardCache()
    {
        self::forget('dashboard_ai_insights');
    }

    /**
     * Bust all known caches (call after big changes like payroll generation)
     */
    public static function bustAllCaches()
    {
        self::clearCache();
    }
}
