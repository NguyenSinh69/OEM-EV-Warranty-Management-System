<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['email', 'password'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            // Validate email format
            if (!$this->validateEmail($data['email'])) {
                $response->error('Invalid email format', 422);
                return;
            }
            
            $result = $this->authService->login($data['email'], $data['password']);
            
            if (!$result) {
                $response->error('Invalid email or password', 401);
                return;
            }
            
            $response->success($result, 'Login successful');
            
        } catch (\Exception $e) {
            $response->error('Login failed: ' . $e->getMessage(), 500);
        }
    }

    public function register(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['username', 'email', 'password', 'first_name', 'last_name'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            // Validate email format
            if (!$this->validateEmail($data['email'])) {
                $response->error('Invalid email format', 422);
                return;
            }
            
            // Validate password strength
            if (strlen($data['password']) < 6) {
                $response->error('Password must be at least 6 characters long', 422);
                return;
            }
            
            $result = $this->authService->register($data);
            
            $response->success($result, 'Registration successful');
            
        } catch (\Exception $e) {
            $response->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function refresh(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            if (!isset($data['refresh_token'])) {
                $response->error('Refresh token is required', 422);
                return;
            }
            
            $result = $this->authService->refreshToken($data['refresh_token']);
            
            if (!$result) {
                $response->error('Invalid or expired refresh token', 401);
                return;
            }
            
            $response->success($result, 'Token refreshed successfully');
            
        } catch (\Exception $e) {
            $response->error('Token refresh failed: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            if (isset($data['refresh_token'])) {
                $this->authService->logout($data['refresh_token']);
            }
            
            $response->success(['logged_out' => true], 'Logout successful');
            
        } catch (\Exception $e) {
            $response->error('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    public function me(Request $request, Response $response): void
    {
        try {
            $token = $request->getBearerToken();
            
            if (!$token) {
                $response->error('Authorization token required', 401);
                return;
            }
            
            $user = $this->authService->validateAccessToken($token);
            
            if (!$user) {
                $response->error('Invalid or expired token', 401);
                return;
            }
            
            $response->success($user);
            
        } catch (\Exception $e) {
            $response->error('Failed to get user info: ' . $e->getMessage(), 500);
        }
    }

    public function changePassword(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['current_password', 'new_password'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            // Validate new password strength
            if (strlen($data['new_password']) < 6) {
                $response->error('New password must be at least 6 characters long', 422);
                return;
            }
            
            $token = $request->getBearerToken();
            if (!$token) {
                $response->error('Authorization token required', 401);
                return;
            }
            
            $user = $this->authService->validateAccessToken($token);
            if (!$user) {
                $response->error('Invalid or expired token', 401);
                return;
            }
            
            $changed = $this->authService->changePassword(
                $user['id'],
                $data['current_password'],
                $data['new_password']
            );
            
            if ($changed) {
                $response->success(['password_changed' => true], 'Password changed successfully');
            } else {
                $response->error('Failed to change password', 500);
            }
            
        } catch (\Exception $e) {
            $response->error('Password change failed: ' . $e->getMessage(), 500);
        }
    }

    public function validateToken(Request $request, Response $response): void
    {
        try {
            $token = $request->getBearerToken();
            
            if (!$token) {
                $response->error('Authorization token required', 401);
                return;
            }
            
            $user = $this->authService->validateAccessToken($token);
            
            if (!$user) {
                $response->error('Invalid or expired token', 401);
                return;
            }
            
            $response->success([
                'valid' => true,
                'user' => $user
            ], 'Token is valid');
            
        } catch (\Exception $e) {
            $response->error('Token validation failed: ' . $e->getMessage(), 500);
        }
    }

    public function checkPermission(Request $request, Response $response): void
    {
        try {
            $permission = $request->getQuery('permission');
            
            if (!$permission) {
                $response->error('Permission parameter is required', 422);
                return;
            }
            
            $token = $request->getBearerToken();
            if (!$token) {
                $response->error('Authorization token required', 401);
                return;
            }
            
            $user = $this->authService->validateAccessToken($token);
            if (!$user) {
                $response->error('Invalid or expired token', 401);
                return;
            }
            
            $hasPermission = $this->authService->hasPermission($user['id'], $permission);
            
            $response->success([
                'permission' => $permission,
                'has_permission' => $hasPermission
            ]);
            
        } catch (\Exception $e) {
            $response->error('Permission check failed: ' . $e->getMessage(), 500);
        }
    }
}