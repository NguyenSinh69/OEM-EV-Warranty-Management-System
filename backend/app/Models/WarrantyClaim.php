<?php

namespace App\Models;

class WarrantyClaim extends BaseModel
{
    protected string $table = 'warranty_claims';
    
    protected array $fillable = [
        'claim_number',
        'vehicle_warranty_id',
        'customer_id',
        'claim_type',
        'priority',
        'status',
        'issue_description',
        'symptoms',
        'fault_code',
        'mileage_at_claim',
        'incident_date',
        'estimated_cost',
        'approved_amount',
        'notes',
        'created_by',
        'assigned_to'
    ];

    public function getWithDetails(int $id): ?array
    {
        $sql = "
            SELECT 
                wc.*,
                vw.warranty_number,
                vw.start_date as warranty_start,
                vw.end_date as warranty_end,
                v.vin,
                v.make,
                v.model,
                v.year,
                c.customer_code,
                c.company_name,
                u_customer.first_name as customer_first_name,
                u_customer.last_name as customer_last_name,
                u_customer.email as customer_email,
                u_customer.phone as customer_phone,
                u_created.first_name as created_by_first_name,
                u_created.last_name as created_by_last_name,
                u_assigned.first_name as assigned_to_first_name,
                u_assigned.last_name as assigned_to_last_name
            FROM warranty_claims wc
            JOIN vehicle_warranties vw ON wc.vehicle_warranty_id = vw.id
            JOIN vehicles v ON vw.vehicle_id = v.id
            JOIN customers c ON wc.customer_id = c.id
            LEFT JOIN users u_customer ON c.user_id = u_customer.id
            LEFT JOIN users u_created ON wc.created_by = u_created.id
            LEFT JOIN users u_assigned ON wc.assigned_to = u_assigned.id
            WHERE wc.id = :id
        ";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }

    public function getClaimsByStatus(string $status): array
    {
        return $this->where(['status' => $status]);
    }

    public function getClaimsByCustomer(int $customerId): array
    {
        return $this->where(['customer_id' => $customerId]);
    }

    public function getClaimsRequiringApproval(): array
    {
        $sql = "
            SELECT wc.*, vw.warranty_number, v.vin, c.company_name
            FROM warranty_claims wc
            JOIN vehicle_warranties vw ON wc.vehicle_warranty_id = vw.id
            JOIN vehicles v ON vw.vehicle_id = v.id
            JOIN customers c ON wc.customer_id = c.id
            WHERE wc.status IN ('submitted', 'under_review')
            ORDER BY wc.priority DESC, wc.reported_date ASC
        ";
        
        return $this->db->fetchAll($sql);
    }

    public function generateClaimNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT COUNT(*) as count FROM warranty_claims WHERE DATE_FORMAT(created_at, '%Y%m') = :yearMonth";
        $result = $this->db->fetch($sql, ['yearMonth' => $year . $month]);
        $count = $result['count'] + 1;
        
        return sprintf('WC%s%s%04d', $year, $month, $count);
    }

    public function updateStatus(int $id, string $status, string $notes = null): int
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($notes) {
            $data['notes'] = $notes;
        }
        
        return $this->db->update($this->table, $data, 'id = :id', ['id' => $id]);
    }

    public function getClaimStatistics(): array
    {
        $sql = "
            SELECT 
                status,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(DAY, reported_date, 
                    CASE WHEN status IN ('completed', 'approved', 'rejected') 
                    THEN updated_at ELSE NOW() END)) as avg_processing_days
            FROM warranty_claims 
            GROUP BY status
        ";
        
        return $this->db->fetchAll($sql);
    }
}