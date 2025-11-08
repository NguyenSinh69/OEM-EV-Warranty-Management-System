<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

abstract class BaseController
{
    protected function validateRequired(array $data, array $required): array
    {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        return $errors;
    }

    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    protected function sanitizeInput(array $data): array
    {
        return array_map(function ($value) {
            return is_string($value) ? trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) : $value;
        }, $data);
    }

    protected function getCurrentUserId(Request $request): ?int
    {
        // This would be implemented with JWT token validation
        // For now, return a default user ID
        return 1; // Admin user
    }

    protected function hasPermission(Request $request, string $permission): bool
    {
        // This would check user permissions based on JWT token
        // For now, return true for all requests
        return true;
    }

    protected function logActivity(string $action, string $entity, int $entityId, array $details = []): void
    {
        // Log activity to system_logs table
        $db = \App\Core\Database::getInstance();
        
        $logData = [
            'user_id' => $this->getCurrentUserId(new \App\Core\Request()),
            'action' => $action,
            'entity_type' => $entity,
            'entity_id' => $entityId,
            'description' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('system_logs', $logData);
    }
}