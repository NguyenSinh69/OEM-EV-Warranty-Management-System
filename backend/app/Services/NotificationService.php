<?php

namespace App\Services;

use App\Core\Database;
use App\Models\WarrantyClaim;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class NotificationService
{
    private Database $db;
    private WarrantyClaim $warrantyClaimModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->warrantyClaimModel = new WarrantyClaim();
    }

    public function sendClaimCreatedNotification(int $claimId): void
    {
        try {
            $claim = $this->warrantyClaimModel->getWithDetails($claimId);
            if (!$claim) {
                return;
            }

            // Send email to customer
            if ($claim['customer_email']) {
                $this->sendEmail(
                    $claim['customer_email'],
                    'Warranty Claim Created',
                    $this->getClaimCreatedEmailTemplate($claim),
                    'warranty_claim',
                    $claimId
                );
            }

            // Send notification to assigned technician
            if ($claim['assigned_to']) {
                $this->sendSystemNotification(
                    $claim['assigned_to'],
                    'New Warranty Claim Assigned',
                    "A new warranty claim #{$claim['claim_number']} has been assigned to you.",
                    'warranty_claim',
                    $claimId
                );
            }

            // Send notification to managers
            $this->sendManagerNotification($claim);

        } catch (\Exception $e) {
            error_log("Failed to send claim created notification: " . $e->getMessage());
        }
    }

    public function sendClaimStatusChangeNotification(int $claimId, string $newStatus): void
    {
        try {
            $claim = $this->warrantyClaimModel->getWithDetails($claimId);
            if (!$claim) {
                return;
            }

            // Send email to customer
            if ($claim['customer_email']) {
                $this->sendEmail(
                    $claim['customer_email'],
                    'Warranty Claim Status Update',
                    $this->getStatusChangeEmailTemplate($claim, $newStatus),
                    'warranty_claim',
                    $claimId
                );
            }

            // Send SMS for critical status changes
            if (in_array($newStatus, ['approved', 'rejected', 'completed']) && $claim['customer_phone']) {
                $message = "Your warranty claim #{$claim['claim_number']} status has been updated to: " . ucfirst($newStatus);
                $this->sendSMS($claim['customer_phone'], $message);
            }

        } catch (\Exception $e) {
            error_log("Failed to send status change notification: " . $e->getMessage());
        }
    }

    public function sendEmail(string $to, string $subject, string $body, string $relatedType = null, int $relatedId = null): bool
    {
        try {
            $mail = new PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? 'mailhog';
            $mail->SMTPAuth = !empty($_ENV['MAIL_USERNAME']);
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? '';
            $mail->Port = $_ENV['MAIL_PORT'] ?? 1025;

            // Email configuration
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@oem-ev.com', $_ENV['MAIL_FROM_NAME'] ?? 'OEM EV Warranty Service');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $sent = $mail->send();

            // Log notification
            $this->logNotification($to, 'email', $subject, $body, $relatedType, $relatedId, $sent ? 'sent' : 'failed');

            return $sent;

        } catch (\Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
            $this->logNotification($to, 'email', $subject, $body, $relatedType, $relatedId, 'failed');
            return false;
        }
    }

    public function sendSMS(string $phone, string $message): bool
    {
        try {
            // For demo purposes, we'll just log the SMS
            // In production, integrate with Twilio or another SMS provider
            
            $this->logNotification($phone, 'sms', 'SMS Notification', $message, null, null, 'sent');
            
            error_log("SMS sent to {$phone}: {$message}");
            return true;

        } catch (\Exception $e) {
            error_log("Failed to send SMS: " . $e->getMessage());
            $this->logNotification($phone, 'sms', 'SMS Notification', $message, null, null, 'failed');
            return false;
        }
    }

    public function sendSystemNotification(int $userId, string $title, string $message, string $relatedType = null, int $relatedId = null): void
    {
        try {
            $this->db->insert('notifications', [
                'recipient_id' => $userId,
                'type' => 'system',
                'title' => $title,
                'message' => $message,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Failed to send system notification: " . $e->getMessage());
        }
    }

    private function sendManagerNotification(array $claim): void
    {
        // Get all managers
        $sql = "SELECT id, email FROM users WHERE role = 'manager' AND status = 'active'";
        $managers = $this->db->fetchAll($sql);

        foreach ($managers as $manager) {
            // Send system notification
            $this->sendSystemNotification(
                $manager['id'],
                'New Warranty Claim Requires Review',
                "A new warranty claim #{$claim['claim_number']} has been submitted and requires review.",
                'warranty_claim',
                $claim['id']
            );

            // Send email for high priority claims
            if ($claim['priority'] === 'high' || $claim['priority'] === 'critical') {
                $this->sendEmail(
                    $manager['email'],
                    'Urgent: High Priority Warranty Claim',
                    $this->getManagerNotificationEmailTemplate($claim),
                    'warranty_claim',
                    $claim['id']
                );
            }
        }
    }

    private function logNotification(string $recipient, string $type, string $title, string $message, string $relatedType = null, int $relatedId = null, string $status = 'pending'): void
    {
        try {
            // For email, we need to get user ID by email
            $userId = null;
            if ($type === 'email') {
                $user = $this->db->fetch("SELECT id FROM users WHERE email = :email", ['email' => $recipient]);
                $userId = $user['id'] ?? null;
            } elseif ($type === 'sms') {
                $user = $this->db->fetch("SELECT id FROM users WHERE phone = :phone", ['phone' => $recipient]);
                $userId = $user['id'] ?? null;
            }

            if ($userId) {
                $this->db->insert('notifications', [
                    'recipient_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'related_type' => $relatedType,
                    'related_id' => $relatedId,
                    'status' => $status,
                    'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            error_log("Failed to log notification: " . $e->getMessage());
        }
    }

    private function getClaimCreatedEmailTemplate(array $claim): string
    {
        return "
        <html>
        <body>
            <h2>Warranty Claim Created</h2>
            <p>Dear {$claim['customer_first_name']} {$claim['customer_last_name']},</p>
            
            <p>Your warranty claim has been successfully created with the following details:</p>
            
            <table border='1' cellpadding='5'>
                <tr><td><strong>Claim Number:</strong></td><td>{$claim['claim_number']}</td></tr>
                <tr><td><strong>Vehicle:</strong></td><td>{$claim['make']} {$claim['model']} ({$claim['year']})</td></tr>
                <tr><td><strong>VIN:</strong></td><td>{$claim['vin']}</td></tr>
                <tr><td><strong>Claim Type:</strong></td><td>" . ucfirst($claim['claim_type']) . "</td></tr>
                <tr><td><strong>Priority:</strong></td><td>" . ucfirst($claim['priority']) . "</td></tr>
                <tr><td><strong>Status:</strong></td><td>" . ucfirst($claim['status']) . "</td></tr>
            </table>
            
            <p><strong>Issue Description:</strong><br>{$claim['issue_description']}</p>
            
            <p>We will review your claim and update you on the progress. You can track your claim status using the claim number provided above.</p>
            
            <p>Thank you for choosing OEM EV.</p>
            
            <p>Best regards,<br>OEM EV Warranty Service Team</p>
        </body>
        </html>
        ";
    }

    private function getStatusChangeEmailTemplate(array $claim, string $newStatus): string
    {
        return "
        <html>
        <body>
            <h2>Warranty Claim Status Update</h2>
            <p>Dear {$claim['customer_first_name']} {$claim['customer_last_name']},</p>
            
            <p>Your warranty claim #{$claim['claim_number']} status has been updated to: <strong>" . ucfirst($newStatus) . "</strong></p>
            
            <table border='1' cellpadding='5'>
                <tr><td><strong>Claim Number:</strong></td><td>{$claim['claim_number']}</td></tr>
                <tr><td><strong>Vehicle:</strong></td><td>{$claim['make']} {$claim['model']} ({$claim['year']})</td></tr>
                <tr><td><strong>New Status:</strong></td><td>" . ucfirst($newStatus) . "</td></tr>
                <tr><td><strong>Updated:</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>
            </table>
            
            " . ($claim['notes'] ? "<p><strong>Notes:</strong><br>{$claim['notes']}</p>" : "") . "
            
            <p>If you have any questions about this update, please contact our customer service team.</p>
            
            <p>Thank you for choosing OEM EV.</p>
            
            <p>Best regards,<br>OEM EV Warranty Service Team</p>
        </body>
        </html>
        ";
    }

    private function getManagerNotificationEmailTemplate(array $claim): string
    {
        return "
        <html>
        <body>
            <h2>High Priority Warranty Claim Alert</h2>
            <p>A high priority warranty claim requires immediate attention:</p>
            
            <table border='1' cellpadding='5'>
                <tr><td><strong>Claim Number:</strong></td><td>{$claim['claim_number']}</td></tr>
                <tr><td><strong>Customer:</strong></td><td>{$claim['company_name']}</td></tr>
                <tr><td><strong>Vehicle:</strong></td><td>{$claim['make']} {$claim['model']} ({$claim['year']})</td></tr>
                <tr><td><strong>Priority:</strong></td><td>" . ucfirst($claim['priority']) . "</td></tr>
                <tr><td><strong>Claim Type:</strong></td><td>" . ucfirst($claim['claim_type']) . "</td></tr>
                <tr><td><strong>Reported:</strong></td><td>{$claim['reported_date']}</td></tr>
            </table>
            
            <p><strong>Issue Description:</strong><br>{$claim['issue_description']}</p>
            
            <p>Please review this claim at your earliest convenience.</p>
            
            <p>Best regards,<br>OEM EV Warranty System</p>
        </body>
        </html>
        ";
    }
}