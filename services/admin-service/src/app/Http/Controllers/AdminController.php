<?php
namespace App\Http\Controllers;

use Core\Database;
use Core\ResponseHelper;
use Core\AuthMiddleware;
use Exception;

class AdminController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // 1️⃣ POST /api/users
    public function createUser() {
        // Require admin role for this action
        AuthMiddleware::authorize('Admin');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['username']) || !isset($data['password']) || !isset($data['role'])) {
            return ResponseHelper::json(['error' => 'Thiếu dữ liệu'], 400);
        }

        try {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $this->db->execute(
                'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)',
                [$data['username'], $passwordHash, $data['role']]
            );

            return ResponseHelper::json(['message' => 'Tạo user thành công'], 201);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi khi tạo user: ' . $e->getMessage()], 500);
        }
    }

    // LOGIN: POST /api/login
    public function login() {
        // Session already started in index.php, no need to start again
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['username']) || !isset($data['password'])) {
            return ResponseHelper::json(['error' => 'Thiếu dữ liệu'], 400);
        }

        try {
            $row = $this->db->query('SELECT id, username, password_hash, role FROM users WHERE username = ?', [$data['username']]);
            if (empty($row)) {
                return ResponseHelper::json(['error' => 'Tên đăng nhập không tồn tại'], 401);
            }
            $user = $row[0];
            if (!password_verify($data['password'], $user['password_hash'])) {
                return ResponseHelper::json(['error' => 'Mật khẩu không đúng'], 401);
            }

            // Set session with proper data
            $_SESSION['user'] = [
                'id' => (int)$user['id'], 
                'username' => $user['username'], 
                'role' => $user['role']
            ];
            
            // Debug: Log successful login
            error_log("User logged in: " . $user['username'] . " with role: " . $user['role']);
            
            return ResponseHelper::json(['message' => 'Đăng nhập thành công', 'user' => $_SESSION['user']]);
        } catch (\Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi khi đăng nhập: ' . $e->getMessage()], 500);
        }
    }

    // LOGOUT: POST /api/logout
    public function logout() {
        // Session already started in index.php
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
        session_destroy();
        return ResponseHelper::json(['success' => true, 'message' => 'Đăng xuất thành công']);
    }

    // GET /api/auth/status
    public function getAuthStatus() {
        // Session already started in index.php
        
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            // Debug: Log auth check
            error_log("Auth check: User found - " . $_SESSION['user']['username']);
            return ResponseHelper::json([
                'success' => true,
                'user' => $_SESSION['user']
            ]);
        } else {
            error_log("Auth check: No user in session");
            return ResponseHelper::json(['success' => false, 'message' => 'Not authenticated']);
        }
    }

    // GET /api/users
    public function getUsers() {
        AuthMiddleware::authorize('Admin');
        try {
            $rows = $this->db->query('SELECT id, username, role, created_at FROM users');
            return ResponseHelper::json($rows);
        } catch (\Exception $e) {
            return ResponseHelper::json(['error' => 'Không thể lấy danh sách users: ' . $e->getMessage()], 500);
        }
    }

    // GET /api/users/{id}
    public function getUser($id) {
        AuthMiddleware::authorize('Admin');
        try {
            $rows = $this->db->query('SELECT id, username, role, created_at FROM users WHERE id = ?', [$id]);
            if (empty($rows)) return ResponseHelper::json(['error' => 'User not found'], 404);
            return ResponseHelper::json($rows[0]);
        } catch (\Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    // PUT /api/users/{id}
    public function updateUser($id) {
        AuthMiddleware::authorize('Admin');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) return ResponseHelper::json(['error' => 'Thiếu dữ liệu'], 400);
        try {
            $params = [];
            $sets = [];
            if (isset($data['username'])) { $sets[] = 'username = ?'; $params[] = $data['username']; }
            if (isset($data['password'])) { $sets[] = 'password_hash = ?'; $params[] = password_hash($data['password'], PASSWORD_DEFAULT); }
            if (isset($data['role'])) { $sets[] = 'role = ?'; $params[] = $data['role']; }
            if (empty($sets)) return ResponseHelper::json(['error' => 'Không có trường để cập nhật'], 400);
            $params[] = $id;
            $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
            $this->db->execute($sql, $params);
            return ResponseHelper::json(['message' => 'Cập nhật thành công']);
        } catch (\Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi cập nhật: ' . $e->getMessage()], 500);
        }
    }

    // DELETE /api/users/{id}
    public function deleteUser($id) {
        AuthMiddleware::authorize('Admin');
        try {
            $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);
            return ResponseHelper::json(['message' => 'Xóa user thành công']);
        } catch (\Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi xóa: ' . $e->getMessage()], 500);
        }
    }

    // 2️⃣ GET /api/service-centers
    public function getServiceCenters() {
        try {
            $rows = $this->db->query('SELECT id, name, location, contact_info FROM service_centers');
            return ResponseHelper::json($rows);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Không thể lấy danh sách trung tâm: ' . $e->getMessage()], 500);
        }
    }

    // 3️⃣ POST /api/assignments
    public function createAssignment() {
        AuthMiddleware::authorize('Admin');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['technician_id']) || !isset($data['service_center_id'])) {
            return ResponseHelper::json(['error' => 'Thiếu dữ liệu'], 400);
        }

        try {
            $this->db->execute(
                'INSERT INTO technician_assignments (technician_id, service_center_id) VALUES (?, ?)',
                [$data['technician_id'], $data['service_center_id']]
            );
            return ResponseHelper::json(['message' => 'Phân công thành công'], 201);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi khi phân công: ' . $e->getMessage()], 500);
        }
    }

    // 4️⃣ GET /api/analytics/failures
    public function getFailureAnalytics() {
        try {
            $rows = $this->db->query('SELECT component_type AS failure_type, COUNT(*) AS count FROM warranty_claims GROUP BY component_type');
            return ResponseHelper::json($rows);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi analytics: ' . $e->getMessage()], 500);
        }
    }

    // 5️⃣ GET /api/analytics/costs
    public function getCostAnalytics() {
        try {
            $rows = $this->db->query("SELECT DATE_FORMAT(completion_date, '%Y-%m') AS month, SUM(repair_cost) AS total FROM warranty_claims GROUP BY DATE_FORMAT(completion_date, '%Y-%m')");
            return ResponseHelper::json($rows);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi analytics: ' . $e->getMessage()], 500);
        }
    }

    // 6️⃣ GET /api/analytics/performance
    public function getPerformanceAnalytics() {
        try {
            $rows = $this->db->query(
                'SELECT sc.name AS service_center, COUNT(wc.id) AS total_claims FROM service_centers sc LEFT JOIN warranty_claims wc ON sc.id = wc.service_center_id WHERE wc.status = "completed" GROUP BY sc.id, sc.name'
            );
            return ResponseHelper::json($rows);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi analytics: ' . $e->getMessage()], 500);
        }
    }

    // 7️⃣ POST /api/reports/export
    public function exportReport() {
        // Accept JSON body with { "format": "csv|excel|pdf", "type": "warranty|users|analytics" }
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $format = strtolower($data['format'] ?? 'csv');
        $type = $data['type'] ?? 'warranty';

        try {
            // Get data based on type
            if ($type === 'warranty') {
                $rows = $this->db->query('SELECT id, vehicle_vin, component_type, status, repair_cost, completion_date FROM warranty_claims');
            } elseif ($type === 'users') {
                $rows = $this->db->query('SELECT id, username, role, created_at FROM users');
            } elseif ($type === 'analytics') {
                $rows = $this->db->query('SELECT component_type, COUNT(*) as failure_count FROM warranty_claims GROUP BY component_type');
            } else {
                $rows = [];
            }

            if ($format === 'csv') {
                // Clean any output buffer before CSV
                if (ob_get_level()) ob_clean();
                
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="report_' . $type . '_' . date('Y-m-d') . '.csv"');
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                
                $output = fopen('php://output', 'w');
                
                // Add UTF-8 BOM for Excel compatibility
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Add headers
                if (!empty($rows)) {
                    fputcsv($output, array_keys($rows[0]));
                    foreach ($rows as $row) {
                        fputcsv($output, $row);
                    }
                } else {
                    fputcsv($output, ['message', 'Không có dữ liệu']);
                }
                
                fclose($output);
                exit();
            }
            
            if ($format === 'excel') {
                // Simple Excel export using XML format
                $filename = 'warranty_export_' . date('Y-m-d_H-i-s') . '.xml';
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
                echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
                echo '  xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
                echo '  xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
                echo '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
                echo '<Worksheet ss:Name="Warranty Export">' . "\n";
                echo '<Table>' . "\n";
                
                // Header row
                echo '<Row>';
                echo '<Cell><Data ss:Type="String">ID</Data></Cell>';
                echo '<Cell><Data ss:Type="String">Username</Data></Cell>';
                echo '<Cell><Data ss:Type="String">Role</Data></Cell>';
                echo '<Cell><Data ss:Type="String">Created</Data></Cell>';
                echo '</Row>' . "\n";
                
                // Data rows
                foreach ($data as $row) {
                    echo '<Row>';
                    echo '<Cell><Data ss:Type="Number">' . ($row['id'] ?? '') . '</Data></Cell>';
                    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['username'] ?? '') . '</Data></Cell>';
                    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['role'] ?? '') . '</Data></Cell>';
                    echo '<Cell><Data ss:Type="String">' . ($row['created_at'] ?? '') . '</Data></Cell>';
                    echo '</Row>' . "\n";
                }
                
                echo '</Table>' . "\n";
                echo '</Worksheet>' . "\n";
                echo '</Workbook>' . "\n";
                exit();
            }
            
            if ($format === 'pdf') {
                // Simple PDF export using HTML to PDF conversion
                $filename = 'warranty_export_' . date('Y-m-d_H-i-s') . '.pdf';
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                // Basic PDF generation using HTML
                $html = '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Warranty Export Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #333; text-align: center; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <h1>OEM EV Warranty System - Export Report</h1>
                    <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody>';
                
                foreach ($data as $row) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($row['id'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($row['username'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($row['role'] ?? '') . '</td>';
                    $html .= '<td>' . htmlspecialchars($row['created_at'] ?? '') . '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody>
                    </table>
                    <div class="footer">
                        © OEM EV Warranty Management System
                    </div>
                </body>
                </html>';
                
                // For demo purposes, we'll send HTML that browsers can save as PDF
                // In production, you'd use a proper PDF library
                header('Content-Type: text/html; charset=UTF-8');
                header('Content-Disposition: inline; filename="' . $filename . '"');
                echo $html;
                exit();
            }

            return ResponseHelper::json(['error' => 'Format không được hỗ trợ. Sử dụng: csv, excel, pdf'], 400);
            
        } catch (\Exception $e) {
            // Log export error for debugging
            $logDir = dirname(__DIR__, 3) . '/tmp';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $msg = date('c') . " - Export error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
            @file_put_contents($logDir . '/export_error.log', $msg, FILE_APPEND);
            return ResponseHelper::json(['error' => 'Lỗi export: ' . $e->getMessage()], 500);
        }
    }

    // 8️⃣ GET /api/dashboard/summary
    public function getDashboardSummary() {
        try {
            $totalUsers = $this->db->query('SELECT COUNT(*) AS total FROM users')[0]['total'] ?? 0;
            $totalServiceCenters = $this->db->query('SELECT COUNT(*) AS total FROM service_centers')[0]['total'] ?? 0;
            $totalWarranties = $this->db->query('SELECT COUNT(*) AS total FROM warranty_claims')[0]['total'] ?? 0;
            $totalCost = $this->db->query('SELECT COALESCE(SUM(repair_cost),0) AS total FROM warranty_claims')[0]['total'] ?? 0;

            return ResponseHelper::json([
                'total_users' => (int)$totalUsers,
                'total_service_centers' => (int)$totalServiceCenters,
                'total_warranties' => (int)$totalWarranties,
                'total_cost' => (float)$totalCost
            ]);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi dashboard: ' . $e->getMessage()], 500);
        }
    }

    // 9️⃣ GET /api/roles
    public function getRoles() {
        try {
            $roles = [
                ['id' => 'Admin', 'name' => 'Quản trị viên', 'description' => 'Toàn quyền hệ thống'],
                ['id' => 'EVMStaff', 'name' => 'Nhân viên EVM', 'description' => 'Quản lý warranty claims'],
                ['id' => 'SCStaff', 'name' => 'Nhân viên trung tâm', 'description' => 'Xử lý claims tại trung tâm'],
                ['id' => 'Technician', 'name' => 'Kỹ thuật viên', 'description' => 'Thực hiện sửa chữa'],
                ['id' => 'Customer', 'name' => 'Khách hàng', 'description' => 'Tạo warranty claims']
            ];
            return ResponseHelper::json($roles);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi lấy roles: ' . $e->getMessage()], 500);
        }
    }

    // 🔟 POST /api/claims/{id}/decision
    public function decideClaim($claimId) {
        AuthMiddleware::authorize('Admin');
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['decision']) || !in_array($data['decision'], ['approved', 'rejected'])) {
            return ResponseHelper::json(['error' => 'Decision phải là approved hoặc rejected'], 400);
        }

        try {
            // Check if claim exists
            $claim = $this->db->query('SELECT id, status FROM warranty_claims WHERE id = ?', [$claimId]);
            if (empty($claim)) {
                return ResponseHelper::json(['error' => 'Claim không tồn tại'], 404);
            }

            // Update claim with admin decision
            $newStatus = $data['decision'] === 'approved' ? 'approved' : 'rejected';
            $notes = $data['notes'] ?? '';
            
            $this->db->execute(
                'UPDATE warranty_claims SET status = ?, admin_notes = ?, reviewed_at = NOW() WHERE id = ?',
                [$newStatus, $notes, $claimId]
            );

            return ResponseHelper::json([
                'message' => 'Quyết định đã được lưu',
                'claim_id' => $claimId,
                'decision' => $data['decision'],
                'status' => $newStatus
            ]);
        } catch (Exception $e) {
            return ResponseHelper::json(['error' => 'Lỗi khi quyết định claim: ' . $e->getMessage()], 500);
        }
    }
}
