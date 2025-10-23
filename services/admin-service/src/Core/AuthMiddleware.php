<?php
namespace Core;

class AuthMiddleware {
    public static function authorize($requiredRole) {
        $headers = getallheaders();
        $role = $headers['X-Role'] ?? null;

        if (!$role || $role !== $requiredRole) {
            ResponseHelper::json(['error' => 'Forbidden: missing or invalid role'], 403);
        }
    }
}
