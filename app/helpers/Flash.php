<?php
/**
 * Flash Messages Helper
 */

class Flash
{
    public static function set($type, $message)
    {
        Session::getInstance()->set('_flash', [
            'type'    => $type,
            'message' => $message
        ]);
    }

    public static function get()
    {
        $flash = Session::getInstance()->get('_flash');
        Session::getInstance()->remove('_flash');
        return $flash;
    }

    public static function has()
    {
        return Session::getInstance()->has('_flash');
    }

    public static function success($message) { self::set('success', $message); }
    public static function error($message)   { self::set('error', $message); }
    public static function warning($message) { self::set('warning', $message); }
    public static function info($message)    { self::set('info', $message); }
}
