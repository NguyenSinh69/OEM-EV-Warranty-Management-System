<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\ClaimApproval;
use App\Models\WarrantyClaim;
use App\Services\NotificationService;

class ApprovalController extends BaseController
{
    private ClaimApproval $approvalModel;
    private WarrantyClaim $claimModel;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->approvalModel = new ClaimApproval();
        $this->claimModel = new WarrantyClaim();
        $this->notificationService = new NotificationService();
    }

    public function getClaimApprovals(Request $request, Response $response): void
    {
        try {
            $claimId = (int)$request->getParam('claimId');
            
            $approvals = $this->approvalModel->getApprovalsByClaimId($claimId);
            
            $response->success($approvals);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch claim approvals: ' . $e->getMessage(), 500);
        }
    }

    public function getPendingApprovals(Request $request, Response $response): void
    {
        try {
            $userId = $request->getQuery('user_id');
            
            if ($userId) {
                $approvals = $this->approvalModel->getPendingApprovalsByUser((int)$userId);
            } else {
                $approvals = $this->approvalModel->getAllPendingApprovals();
            }
            
            $response->success($approvals);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch pending approvals: ' . $e->getMessage(), 500);
        }
    }

    public function processApproval(Request $request, Response $response): void
    {
        try {
            $claimId = (int)$request->getParam('claimId');
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['approval_id', 'status'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            $approvalId = (int)$data['approval_id'];
            $status = $data['status'];
            $comments = $data['comments'] ?? null;
            $approvedAmount = isset($data['approved_amount']) ? (float)$data['approved_amount'] : null;
            
            // Validate status
            $validStatuses = ['approved', 'rejected', 'escalated'];
            if (!in_array($status, $validStatuses)) {
                $response->error('Invalid approval status', 422);
                return;
            }
            
            // Check if approval exists and belongs to the claim
            $approval = $this->approvalModel->find($approvalId);
            if (!$approval || $approval['claim_id'] != $claimId) {
                $response->error('Approval not found or does not belong to this claim', 404);
                return;
            }
            
            // Check if approval is still pending
            if ($approval['status'] !== 'pending') {
                $response->error('Approval has already been processed', 422);
                return;
            }
            
            // Check permissions (user should be the assigned approver)
            $currentUserId = $this->getCurrentUserId($request);
            if ($approval['approver_id'] != $currentUserId) {
                $response->error('You are not authorized to process this approval', 403);
                return;
            }
            
            // Process the approval
            $processed = $this->approvalModel->processApproval(
                $approvalId,
                $status,
                $comments,
                $approvedAmount
            );
            
            if ($processed) {
                // Log activity
                $this->logActivity('approval_processed', 'claim_approval', $approvalId, [
                    'claim_id' => $claimId,
                    'status' => $status,
                    'comments' => $comments
                ]);
                
                // Send notification
                $this->notificationService->sendApprovalProcessedNotification($claimId, $status, $comments);
                
                $response->success([
                    'processed' => true,
                    'status' => $status
                ], 'Approval processed successfully');
            } else {
                $response->error('Failed to process approval', 500);
            }
            
        } catch (\Exception $e) {
            $response->error('Failed to process approval: ' . $e->getMessage(), 500);
        }
    }

    public function initializeWorkflow(Request $request, Response $response): void
    {
        try {
            $claimId = (int)$request->getParam('claimId');
            
            // Check if claim exists
            $claim = $this->claimModel->find($claimId);
            if (!$claim) {
                $response->error('Claim not found', 404);
                return;
            }
            
            // Check if workflow already exists
            $existingApprovals = $this->approvalModel->getApprovalsByClaimId($claimId);
            if (!empty($existingApprovals)) {
                $response->error('Approval workflow already exists for this claim', 422);
                return;
            }
            
            // Initialize approval workflow
            $this->approvalModel->initializeApprovalWorkflow($claimId);
            
            // Update claim status
            $this->claimModel->updateStatus($claimId, 'under_review');
            
            // Log activity
            $this->logActivity('workflow_initialized', 'warranty_claim', $claimId, [
                'claim_number' => $claim['claim_number']
            ]);
            
            // Send notifications to approvers
            $this->notificationService->sendApprovalRequestNotifications($claimId);
            
            $response->success([
                'initialized' => true,
                'claim_id' => $claimId
            ], 'Approval workflow initialized successfully');
            
        } catch (\Exception $e) {
            $response->error('Failed to initialize approval workflow: ' . $e->getMessage(), 500);
        }
    }

    public function getApprovalStatistics(Request $request, Response $response): void
    {
        try {
            $statistics = $this->approvalModel->getApprovalStatistics();
            
            // Process statistics for better presentation
            $processed = [];
            foreach ($statistics as $stat) {
                $level = $stat['approval_level'];
                if (!isset($processed[$level])) {
                    $processed[$level] = [
                        'level' => $level,
                        'total' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                        'pending' => 0,
                        'escalated' => 0,
                        'avg_processing_hours' => 0
                    ];
                }
                
                $processed[$level][$stat['status']] = (int)$stat['count'];
                $processed[$level]['total'] += (int)$stat['count'];
                $processed[$level]['avg_processing_hours'] = round((float)$stat['avg_processing_hours'], 2);
            }
            
            $response->success(array_values($processed));
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch approval statistics: ' . $e->getMessage(), 500);
        }
    }

    public function getWorkflowHistory(Request $request, Response $response): void
    {
        try {
            $claimId = (int)$request->getParam('claimId');
            
            $approvals = $this->approvalModel->getApprovalsByClaimId($claimId);
            
            // Add workflow status
            $workflowStatus = 'pending';
            $allApproved = true;
            $hasRejected = false;
            
            foreach ($approvals as $approval) {
                if ($approval['status'] === 'rejected') {
                    $hasRejected = true;
                    break;
                }
                if ($approval['status'] !== 'approved') {
                    $allApproved = false;
                }
            }
            
            if ($hasRejected) {
                $workflowStatus = 'rejected';
            } elseif ($allApproved && !empty($approvals)) {
                $workflowStatus = 'approved';
            } elseif (!empty($approvals)) {
                $workflowStatus = 'in_progress';
            }
            
            $response->success([
                'workflow_status' => $workflowStatus,
                'approvals' => $approvals,
                'total_levels' => count($approvals),
                'completed_levels' => count(array_filter($approvals, function($a) {
                    return in_array($a['status'], ['approved', 'rejected']);
                }))
            ]);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch workflow history: ' . $e->getMessage(), 500);
        }
    }

    public function bulkApprove(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            if (!isset($data['approval_ids']) || !is_array($data['approval_ids'])) {
                $response->error('approval_ids array is required', 422);
                return;
            }
            
            $approvalIds = array_map('intval', $data['approval_ids']);
            $comments = $data['comments'] ?? null;
            $currentUserId = $this->getCurrentUserId($request);
            
            $processed = 0;
            $errors = [];
            
            foreach ($approvalIds as $approvalId) {
                try {
                    $approval = $this->approvalModel->find($approvalId);
                    
                    if (!$approval) {
                        $errors[] = "Approval {$approvalId} not found";
                        continue;
                    }
                    
                    if ($approval['approver_id'] != $currentUserId) {
                        $errors[] = "Not authorized for approval {$approvalId}";
                        continue;
                    }
                    
                    if ($approval['status'] !== 'pending') {
                        $errors[] = "Approval {$approvalId} already processed";
                        continue;
                    }
                    
                    $this->approvalModel->processApproval($approvalId, 'approved', $comments);
                    $processed++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to process approval {$approvalId}: " . $e->getMessage();
                }
            }
            
            $response->success([
                'processed' => $processed,
                'total' => count($approvalIds),
                'errors' => $errors
            ], "Bulk approval completed. {$processed} approvals processed.");
            
        } catch (\Exception $e) {
            $response->error('Failed to process bulk approval: ' . $e->getMessage(), 500);
        }
    }
}