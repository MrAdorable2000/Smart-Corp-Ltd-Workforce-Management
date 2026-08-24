<?php
/**
 * URL Helper
 */

class Url
{
    public static function to($path = '')
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }

    public static function asset($path)
    {
        return ASSET_URL . '/' . ltrim($path, '/');
    }

    public static function upload($path)
    {
        return UPLOAD_URL . '/' . ltrim($path, '/');
    }

    public static function current()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function isActive($path)
    {
        $current = parse_url(self::current(), PHP_URL_PATH);
        $current = str_replace(BASE_URL, '', $current);
        $current = ltrim($current, '/');
        if ($path === '/' && $current === '') return true;
        return strpos($current, ltrim($path, '/')) === 0;
    }
}

function url($path = '') { return Url::to($path); }
function asset($path)    { return Url::asset($path); }
