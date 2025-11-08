<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Core\Database;

class AuthService
{
    private Database $db;
    private string $secretKey;
    private string $algorithm;
    private int $expiration;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-this-in-production';
        $this->algorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $this->expiration = (int)($_ENV['JWT_EXPIRATION'] ?? 3600); // 1 hour
    }

    public function login(string $email, string $password): ?array
    {
        // Find user by email
        $user = $this->db->fetch(
            "SELECT id, username, email, password_hash, first_name, last_name, role, status FROM users WHERE email = :email",
            ['email' => $email]
        );

        if (!$user) {
            return null;
        }

        // Check if user is active
        if ($user['status'] !== 'active') {
            throw new \Exception('User account is not active');
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Generate tokens
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user['id']);

        // Store refresh token
        $this->storeRefreshToken($user['id'], $refreshToken);

        // Log login activity
        $this->logActivity($user['id'], 'login', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        return [
            'user' => $this->formatUserData($user),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->expiration
        ];
    }

    public function refreshToken(string $refreshToken): ?array
    {
        // Verify refresh token
        $tokenData = $this->verifyRefreshToken($refreshToken);
        
        if (!$tokenData) {
            return null;
        }

        // Get user data
        $user = $this->db->fetch(
            "SELECT id, username, email, first_name, last_name, role, status FROM users WHERE id = :id",
            ['id' => $tokenData['user_id']]
        );

        if (!$user || $user['status'] !== 'active') {
            return null;
        }

        // Generate new access token
        $accessToken = $this->generateAccessToken($user);

        return [
            'user' => $this->formatUserData($user),
            'access_token' => $accessToken,
            'expires_in' => $this->expiration
        ];
    }

    public function logout(string $refreshToken): bool
    {
        // Remove refresh token from database
        return $this->db->delete('user_refresh_tokens', 'token = :token', ['token' => $refreshToken]) > 0;
    }

    public function validateAccessToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array)$decoded;

            // Check if token is expired
            if ($payload['exp'] < time()) {
                return null;
            }

            // Get user data
            $user = $this->db->fetch(
                "SELECT id, username, email, first_name, last_name, role, status FROM users WHERE id = :id",
                ['id' => $payload['user_id']]
            );

            if (!$user || $user['status'] !== 'active') {
                return null;
            }

            return $this->formatUserData($user);

        } catch (\Exception $e) {
            return null;
        }
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        // Get user role
        $user = $this->db->fetch("SELECT role FROM users WHERE id = :id", ['id' => $userId]);
        
        if (!$user) {
            return false;
        }

        $role = $user['role'];
        $permissions = $this->getRolePermissions($role);

        return in_array($permission, $permissions) || in_array('*', $permissions);
    }

    public function register(array $userData): array
    {
        // Check if email already exists
        $existingUser = $this->db->fetch("SELECT id FROM users WHERE email = :email", ['email' => $userData['email']]);
        
        if ($existingUser) {
            throw new \Exception('Email already exists');
        }

        // Check if username already exists
        $existingUsername = $this->db->fetch("SELECT id FROM users WHERE username = :username", ['username' => $userData['username']]);
        
        if ($existingUsername) {
            throw new \Exception('Username already exists');
        }

        // Hash password
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);

        // Set default values
        $userData['role'] = $userData['role'] ?? 'customer';
        $userData['status'] = 'active';
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['updated_at'] = date('Y-m-d H:i:s');

        // Create user
        $userId = $this->db->insert('users', $userData);

        // Get created user
        $user = $this->db->fetch("SELECT id, username, email, first_name, last_name, role, status FROM users WHERE id = :id", ['id' => $userId]);

        // Generate tokens
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user['id']);

        // Store refresh token
        $this->storeRefreshToken($user['id'], $refreshToken);

        return [
            'user' => $this->formatUserData($user),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->expiration
        ];
    }

    private function generateAccessToken(array $user): string
    {
        $payload = [
            'iss' => 'oem-ev-warranty-service',
            'aud' => 'oem-ev-warranty-client',
            'iat' => time(),
            'exp' => time() + $this->expiration,
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    private function generateRefreshToken(int $userId): string
    {
        $payload = [
            'iss' => 'oem-ev-warranty-service',
            'aud' => 'oem-ev-warranty-client',
            'iat' => time(),
            'exp' => time() + (30 * 24 * 60 * 60), // 30 days
            'user_id' => $userId,
            'type' => 'refresh'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    private function verifyRefreshToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array)$decoded;

            // Check if it's a refresh token
            if (($payload['type'] ?? '') !== 'refresh') {
                return null;
            }

            // Check if token exists in database and is not expired
            $tokenRecord = $this->db->fetch(
                "SELECT user_id, expires_at FROM user_refresh_tokens WHERE token = :token AND expires_at > NOW()",
                ['token' => $token]
            );

            return $tokenRecord;

        } catch (\Exception $e) {
            return null;
        }
    }

    private function storeRefreshToken(int $userId, string $token): void
    {
        // Remove old refresh tokens for this user
        $this->db->delete('user_refresh_tokens', 'user_id = :user_id', ['user_id' => $userId]);

        // Store new refresh token
        $this->db->insert('user_refresh_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)), // 30 days
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function formatUserData(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }

    private function getRolePermissions(string $role): array
    {
        $permissions = [
            'admin' => ['*'], // All permissions
            'manager' => [
                'warranty_claims.view',
                'warranty_claims.create',
                'warranty_claims.update',
                'warranty_claims.approve',
                'warranty_claims.statistics',
                'customers.view',
                'customers.create',
                'customers.update',
                'vehicles.view',
                'vehicles.create',
                'vehicles.update',
                'approvals.process',
                'reports.view',
                'notifications.send'
            ],
            'technician' => [
                'warranty_claims.view',
                'warranty_claims.create',
                'warranty_claims.update',
                'customers.view',
                'vehicles.view',
                'approvals.level1'
            ],
            'customer_service' => [
                'warranty_claims.view',
                'warranty_claims.create',
                'customers.view',
                'customers.create',
                'customers.update',
                'vehicles.view',
                'vehicles.create',
                'vehicles.update',
                'notifications.send'
            ],
            'customer' => [
                'warranty_claims.view_own',
                'warranty_claims.create_own',
                'customers.view_own',
                'vehicles.view_own'
            ]
        ];

        return $permissions[$role] ?? [];
    }

    private function logActivity(int $userId, string $action, array $details = []): void
    {
        $this->db->insert('system_logs', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'auth',
            'description' => json_encode($details),
            'ip_address' => $details['ip_address'] ?? null,
            'user_agent' => $details['user_agent'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        // Get current user
        $user = $this->db->fetch(
            "SELECT password_hash FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new \Exception('Current password is incorrect');
        }

        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updated = $this->db->update(
            'users',
            ['password_hash' => $newPasswordHash, 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $userId]
        );

        if ($updated) {
            // Invalidate all refresh tokens for this user
            $this->db->delete('user_refresh_tokens', 'user_id = :user_id', ['user_id' => $userId]);
            
            // Log activity
            $this->logActivity($userId, 'password_changed');
        }

        return $updated > 0;
    }
}