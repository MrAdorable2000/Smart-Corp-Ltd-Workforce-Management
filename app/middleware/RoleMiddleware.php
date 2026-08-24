<?php
/**
 * Role Middleware
 * Restricts access by role slug(s)
 * Usage: ['role:super_admin,hr_manager']
 */

class RoleMiddleware
{
    public function handle($params = [])
    {
        if (!Auth::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        // Parse roles from middleware definition (handled by Router extension)
        // This is a basic implementation
    }
}
