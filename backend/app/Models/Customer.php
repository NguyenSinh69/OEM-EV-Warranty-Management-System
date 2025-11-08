<?php

namespace App\Models;

class Customer extends BaseModel
{
    protected string $table = 'customers';
    
    protected array $fillable = [
        'user_id',
        'customer_code',
        'company_name',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'contact_person'
    ];

    public function getCustomerWithUser(int $id): ?array
    {
        $sql = "
            SELECT 
                c.*,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.phone,
                u.status as user_status
            FROM customers c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = :id
        ";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }

    public function getCustomerByCode(string $customerCode): ?array
    {
        return $this->findBy('customer_code', $customerCode);
    }

    public function generateCustomerCode(): string
    {
        $year = date('Y');
        
        $sql = "SELECT COUNT(*) as count FROM customers WHERE DATE_FORMAT(created_at, '%Y') = :year";
        $result = $this->db->fetch($sql, ['year' => $year]);
        $count = $result['count'] + 1;
        
        return sprintf('CUST%s%06d', $year, $count);
    }

    public function getCustomerVehicles(int $customerId): array
    {
        $sql = "
            SELECT 
                v.*,
                COUNT(vw.id) as warranty_count,
                COUNT(wc.id) as claim_count
            FROM vehicles v
            LEFT JOIN vehicle_warranties vw ON v.id = vw.vehicle_id AND vw.status = 'active'
            LEFT JOIN warranty_claims wc ON vw.id = wc.vehicle_warranty_id
            WHERE v.customer_id = :customer_id
            GROUP BY v.id
            ORDER BY v.created_at DESC
        ";
        
        return $this->db->fetchAll($sql, ['customer_id' => $customerId]);
    }

    public function getCustomerClaims(int $customerId): array
    {
        $sql = "
            SELECT 
                wc.*,
                v.vin,
                v.make,
                v.model,
                vw.warranty_number
            FROM warranty_claims wc
            JOIN vehicle_warranties vw ON wc.vehicle_warranty_id = vw.id
            JOIN vehicles v ON vw.vehicle_id = v.id
            WHERE wc.customer_id = :customer_id
            ORDER BY wc.reported_date DESC
        ";
        
        return $this->db->fetchAll($sql, ['customer_id' => $customerId]);
    }
}