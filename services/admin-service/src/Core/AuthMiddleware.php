<?php
namespace Core;

class AuthMiddleware {
    /**
     * Authorize by session user role or by X-Role header (dev convenience).
     * If $requiredRole is null, only require authenticated user.
     */
    public static function authorize($requiredRole = null) {
        // prefer session-based auth
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $user = $_SESSION['user'];
            if ($requiredRole === null) return true;
            // Admin can access everything
            if (($user['role'] ?? null) === 'Admin') return true;
            if (($user['role'] ?? null) === $requiredRole) return true;
            \Core\ResponseHelper::json(['error' => 'Forbidden: insufficient role'], 403);
        }

        // fallback to header-based dev role
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $role = $headers['X-Role'] ?? null;
            if ($role) {
                if ($requiredRole === null) return true;
                if ($role === 'Admin' || $role === $requiredRole) return true;
            }
        }

        \Core\ResponseHelper::json(['error' => 'Forbidden: missing or invalid role'], 403);
    }
}
