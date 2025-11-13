<?php

namespace App\Models;

class ClaimApproval extends BaseModel
{
    protected string $table = 'claim_approvals';
    
    protected array $fillable = [
        'claim_id',
        'approver_id',
        'approval_level',
        'status',
        'comments',
        'approved_amount',
        'decision_date'
    ];

    public function getApprovalsByClaimId(int $claimId): array
    {
        $sql = "
            SELECT 
                ca.*,
                u.first_name,
                u.last_name,
                u.email,
                u.role
            FROM claim_approvals ca
            JOIN users u ON ca.approver_id = u.id
            WHERE ca.claim_id = :claim_id
            ORDER BY ca.approval_level ASC, ca.created_at ASC
        ";
        
        return $this->db->fetchAll($sql, ['claim_id' => $claimId]);
    }

    public function getPendingApprovalsByUser(int $userId): array
    {
        $sql = "
            SELECT 
                ca.*,
                wc.claim_number,
                wc.claim_type,
                wc.priority,
                wc.estimated_cost,
                wc.issue_description,
                c.company_name,
                v.vin,
                v.make,
                v.model
            FROM claim_approvals ca
            JOIN warranty_claims wc ON ca.claim_id = wc.id
            JOIN vehicle_warranties vw ON wc.vehicle_warranty_id = vw.id
            JOIN vehicles v ON vw.vehicle_id = v.id
            JOIN customers c ON wc.customer_id = c.id
            WHERE ca.approver_id = :user_id 
            AND ca.status = 'pending'
            ORDER BY wc.priority DESC, ca.created_at ASC
        ";
        
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    public function getAllPendingApprovals(): array
    {
        $sql = "
            SELECT 
                ca.*,
                wc.claim_number,
                wc.claim_type,
                wc.priority,
                wc.estimated_cost,
                wc.issue_description,
                c.company_name,
                v.vin,
                v.make,
                v.model,
                u.first_name as approver_first_name,
                u.last_name as approver_last_name,
                u.role as approver_role
            FROM claim_approvals ca
            JOIN warranty_claims wc ON ca.claim_id = wc.id
            JOIN vehicle_warranties vw ON wc.vehicle_warranty_id = vw.id
            JOIN vehicles v ON vw.vehicle_id = v.id
            JOIN customers c ON wc.customer_id = c.id
            JOIN users u ON ca.approver_id = u.id
            WHERE ca.status = 'pending'
            ORDER BY wc.priority DESC, ca.approval_level ASC, ca.created_at ASC
        ";
        
        return $this->db->fetchAll($sql);
    }

    public function processApproval(int $approvalId, string $status, string $comments = null, float $approvedAmount = null): bool
    {
        try {
            $this->db->getConnection()->beginTransaction();
            
            // Update approval record
            $data = [
                'status' => $status,
                'decision_date' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($comments) {
                $data['comments'] = $comments;
            }
            
            if ($approvedAmount !== null) {
                $data['approved_amount'] = $approvedAmount;
            }
            
            $this->update($approvalId, $data);
            
            // Get approval details
            $approval = $this->find($approvalId);
            if (!$approval) {
                throw new \Exception('Approval not found');
            }
            
            // Check if this is the final approval level
            if ($status === 'approved') {
                $this->checkAndUpdateClaimStatus($approval['claim_id'], $approval['approval_level']);
            } elseif ($status === 'rejected') {
                // If rejected, reject the entire claim
                $claimModel = new WarrantyClaim();
                $claimModel->updateStatus($approval['claim_id'], 'rejected', $comments);
            } elseif ($status === 'escalated') {
                // Create next level approval
                $this->createEscalatedApproval($approval['claim_id'], $approval['approval_level']);
            }
            
            $this->db->getConnection()->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            throw $e;
        }
    }

    private function checkAndUpdateClaimStatus(int $claimId, int $currentLevel): void
    {
        // Check if all required approvals are completed
        $sql = "
            SELECT 
                COUNT(*) as total_approvals,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
            FROM claim_approvals 
            WHERE claim_id = :claim_id AND approval_level <= :current_level
        ";
        
        $result = $this->db->fetch($sql, [
            'claim_id' => $claimId,
            'current_level' => $currentLevel
        ]);
        
        // Get claim details to determine required approval levels
        $claimModel = new WarrantyClaim();
        $claim = $claimModel->find($claimId);
        
        $requiredLevels = $this->getRequiredApprovalLevels($claim);
        
        // Check if all required levels are approved
        $allApproved = true;
        foreach ($requiredLevels as $level) {
            $levelApproval = $this->db->fetch(
                "SELECT status FROM claim_approvals WHERE claim_id = :claim_id AND approval_level = :level",
                ['claim_id' => $claimId, 'level' => $level]
            );
            
            if (!$levelApproval || $levelApproval['status'] !== 'approved') {
                $allApproved = false;
                break;
            }
        }
        
        if ($allApproved) {
            $claimModel->updateStatus($claimId, 'approved');
        }
    }

    private function createEscalatedApproval(int $claimId, int $currentLevel): void
    {
        $nextLevel = $currentLevel + 1;
        
        // Get appropriate approver for next level
        $approver = $this->getApproverForLevel($nextLevel);
        
        if ($approver) {
            $this->create([
                'claim_id' => $claimId,
                'approver_id' => $approver['id'],
                'approval_level' => $nextLevel,
                'status' => 'pending'
            ]);
        }
    }

    public function initializeApprovalWorkflow(int $claimId): void
    {
        $claimModel = new WarrantyClaim();
        $claim = $claimModel->find($claimId);
        
        if (!$claim) {
            throw new \Exception('Claim not found');
        }
        
        $requiredLevels = $this->getRequiredApprovalLevels($claim);
        
        foreach ($requiredLevels as $level) {
            $approver = $this->getApproverForLevel($level);
            
            if ($approver) {
                $this->create([
                    'claim_id' => $claimId,
                    'approver_id' => $approver['id'],
                    'approval_level' => $level,
                    'status' => 'pending'
                ]);
            }
        }
    }

    private function getRequiredApprovalLevels(array $claim): array
    {
        $levels = [];
        $estimatedCost = (float)$claim['estimated_cost'];
        $priority = $claim['priority'];
        
        // Level 1: Technician approval (always required)
        $levels[] = 1;
        
        // Level 2: Supervisor approval (for medium+ priority or cost > 1000)
        if ($priority !== 'low' || $estimatedCost > 1000) {
            $levels[] = 2;
        }
        
        // Level 3: Manager approval (for high+ priority or cost > 5000)
        if (in_array($priority, ['high', 'critical']) || $estimatedCost > 5000) {
            $levels[] = 3;
        }
        
        // Level 4: Director approval (for critical priority or cost > 20000)
        if ($priority === 'critical' || $estimatedCost > 20000) {
            $levels[] = 4;
        }
        
        return $levels;
    }

    private function getApproverForLevel(int $level): ?array
    {
        $roleMap = [
            1 => 'technician',
            2 => 'supervisor',
            3 => 'manager',
            4 => 'admin'
        ];
        
        $role = $roleMap[$level] ?? 'manager';
        
        // Get an available approver for this role
        $sql = "
            SELECT id, first_name, last_name, email 
            FROM users 
            WHERE role = :role AND status = 'active'
            ORDER BY RAND()
            LIMIT 1
        ";
        
        return $this->db->fetch($sql, ['role' => $role]);
    }

    public function getApprovalStatistics(): array
    {
        $sql = "
            SELECT 
                approval_level,
                status,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(HOUR, created_at, 
                    CASE WHEN decision_date IS NOT NULL 
                    THEN decision_date ELSE NOW() END)) as avg_processing_hours
            FROM claim_approvals 
            GROUP BY approval_level, status
            ORDER BY approval_level, status
        ";
        
        return $this->db->fetchAll($sql);
    }
}