<?php
/**
 * CSRF Protection Helper
 */

class CSRF
{
    /**
     * Generate or retrieve existing token
     */
    public static function token()
    {
        return Session::getInstance()->csrfToken();
    }

    /**
     * Verify token against session
     */
    public static function verify($token)
    {
        if (empty($token)) return false;
        $sessionToken = Session::getInstance()->get('_csrf_token');
        if (empty($sessionToken)) return false;
        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate hidden input field for forms
     */
    public static function field()
    {
        $token = self::token();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Generate meta tag for JS usage
     */
    public static function meta()
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::token()) . '">';
    }
}
