<?php
namespace App\Http\Controllers;

use Core\ResponseHelper;
use Core\AuthMiddleware;

class AdminController {
    public function getRoles() {
        AuthMiddleware::authorize('Admin');

        $roles = [
            ['code' => 'SC_Staff', 'name' => 'Service Center Staff'],
            ['code' => 'SC_Technician', 'name' => 'Service Center Technician'],
            ['code' => 'EVM_Staff', 'name' => 'Manufacturer Staff'],
            ['code' => 'Admin', 'name' => 'System Administrator']
        ];

        ResponseHelper::json(['roles' => $roles]);
    }

    public function decideClaim($claimId) {
        AuthMiddleware::authorize('EVM_Staff');

        $body = json_decode(file_get_contents('php://input'), true);
        $decision = $body['decision'] ?? 'pending';

        ResponseHelper::json([
            'claim_id' => $claimId,
            'decision' => $decision
        ]);
    }
}
