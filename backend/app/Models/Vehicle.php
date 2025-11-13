<?php

namespace App\Models;

class Vehicle extends BaseModel
{
    protected string $table = 'vehicles';
    
    protected array $fillable = [
        'customer_id',
        'vin',
        'make',
        'model',
        'year',
        'color',
        'battery_capacity',
        'motor_power',
        'manufacturing_date',
        'delivery_date',
        'mileage',
        'status'
    ];

    public function getVehicleWithCustomer(int $id): ?array
    {
        $sql = "
            SELECT 
                v.*,
                c.customer_code,
                c.company_name,
                u.first_name as owner_first_name,
                u.last_name as owner_last_name,
                u.email as owner_email
            FROM vehicles v
            JOIN customers c ON v.customer_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE v.id = :id
        ";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }

    public function getVehicleByVin(string $vin): ?array
    {
        return $this->findBy('vin', $vin);
    }

    public function getActiveWarranties(int $vehicleId): array
    {
        $sql = "
            SELECT 
                vw.*,
                wp.policy_name,
                wp.coverage_type,
                wp.duration_months
            FROM vehicle_warranties vw
            JOIN warranty_policies wp ON vw.policy_id = wp.id
            WHERE vw.vehicle_id = :vehicle_id 
            AND vw.status = 'active'
            AND vw.end_date >= CURDATE()
            ORDER BY vw.end_date DESC
        ";
        
        return $this->db->fetchAll($sql, ['vehicle_id' => $vehicleId]);
    }

    public function getClaims(int $vehicleId): array
    {
        $sql = "
            SELECT 
                wc.*,
                vw.warranty_number
            FROM warranty_claims wc
            JOIN vehicle_warranties vw ON wc.vehicle_warranty_id = vw.id
            WHERE vw.vehicle_id = :vehicle_id
            ORDER BY wc.reported_date DESC
        ";
        
        return $this->db->fetchAll($sql, ['vehicle_id' => $vehicleId]);
    }
}