<?php

namespace App\Models;

class VehicleWarranty extends BaseModel
{
    protected string $table = 'vehicle_warranties';
    
    protected array $fillable = [
        'vehicle_id',
        'policy_id',
        'warranty_number',
        'start_date',
        'end_date',
        'status'
    ];

    public function getActiveWarranty(int $vehicleId): ?array
    {
        $sql = "
            SELECT vw.*, wp.policy_name, wp.coverage_type, wp.duration_months
            FROM vehicle_warranties vw
            JOIN warranty_policies wp ON vw.policy_id = wp.id
            WHERE vw.vehicle_id = :vehicle_id 
            AND vw.status = 'active'
            AND vw.end_date >= CURDATE()
            ORDER BY vw.end_date DESC
            LIMIT 1
        ";
        
        return $this->db->fetch($sql, ['vehicle_id' => $vehicleId]);
    }

    public function getWarrantiesByVin(string $vin): array
    {
        $sql = "
            SELECT vw.*, wp.policy_name, wp.coverage_type, v.vin
            FROM vehicle_warranties vw
            JOIN warranty_policies wp ON vw.policy_id = wp.id
            JOIN vehicles v ON vw.vehicle_id = v.id
            WHERE v.vin = :vin
            ORDER BY vw.start_date DESC
        ";
        
        return $this->db->fetchAll($sql, ['vin' => $vin]);
    }

    public function checkWarrantyValidity(int $vehicleId, string $claimDate = null): array
    {
        $claimDate = $claimDate ?: date('Y-m-d');
        
        $sql = "
            SELECT 
                vw.*,
                wp.policy_name,
                wp.coverage_type,
                v.mileage,
                wp.mileage_limit,
                CASE 
                    WHEN vw.end_date < :claim_date THEN 'expired'
                    WHEN wp.mileage_limit IS NOT NULL AND v.mileage > wp.mileage_limit THEN 'mileage_exceeded'
                    WHEN vw.status != 'active' THEN 'inactive'
                    ELSE 'valid'
                END as validity_status
            FROM vehicle_warranties vw
            JOIN warranty_policies wp ON vw.policy_id = wp.id
            JOIN vehicles v ON vw.vehicle_id = v.id
            WHERE vw.vehicle_id = :vehicle_id
            AND vw.status = 'active'
            ORDER BY vw.start_date DESC
        ";
        
        return $this->db->fetchAll($sql, [
            'vehicle_id' => $vehicleId,
            'claim_date' => $claimDate
        ]);
    }

    public function generateWarrantyNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT COUNT(*) as count FROM vehicle_warranties WHERE DATE_FORMAT(created_at, '%Y%m') = :yearMonth";
        $result = $this->db->fetch($sql, ['yearMonth' => $year . $month]);
        $count = $result['count'] + 1;
        
        return sprintf('WR%s%s%04d', $year, $month, $count);
    }

    public function getExpiringWarranties(int $days = 30): array
    {
        $sql = "
            SELECT 
                vw.*,
                v.vin,
                v.make,
                v.model,
                c.company_name,
                u.email as customer_email,
                wp.policy_name
            FROM vehicle_warranties vw
            JOIN vehicles v ON vw.vehicle_id = v.id
            JOIN customers c ON v.customer_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            JOIN warranty_policies wp ON vw.policy_id = wp.id
            WHERE vw.status = 'active'
            AND vw.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            ORDER BY vw.end_date ASC
        ";
        
        return $this->db->fetchAll($sql, ['days' => $days]);
    }
}