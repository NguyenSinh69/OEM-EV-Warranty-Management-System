<?php
// Database configuration and sample data for EVM Warranty System

class MockDatabase {
    
    public static function getCustomers() {
        return [
            [
                'id' => 1,
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'phone' => '0901234567',
                'address' => 'Hà Nội',
                'date_of_birth' => '1990-01-01',
                'id_number' => '123456789',
                'status' => 'active',
                'role' => 'customer'
            ],
            [
                'id' => 2,
                'name' => 'Trần Thị B',
                'email' => 'tranthib@example.com',
                'phone' => '0912345678',
                'address' => 'TP.HCM',
                'date_of_birth' => '1985-05-15',
                'id_number' => '987654321',
                'status' => 'active',
                'role' => 'customer'
            ],
            [
                'id' => 3,
                'name' => 'Admin User',
                'email' => 'admin@evm.com',
                'phone' => '0999999999',
                'address' => 'VinFast HQ',
                'date_of_birth' => '1980-01-01',
                'id_number' => '999999999',
                'status' => 'active',
                'role' => 'admin'
            ],
            [
                'id' => 4,
                'name' => 'EVM Staff',
                'email' => 'staff@evm.com',
                'phone' => '0888888888',
                'address' => 'VinFast Office',
                'date_of_birth' => '1985-06-15',
                'id_number' => '888888888',
                'status' => 'active',
                'role' => 'evm_staff'
            ]
        ];
    }

    public static function getVehicles() {
        return [
            [
                'id' => 1,
                'vin' => 'VF3ABCDEF12345678',
                'model' => 'VinFast VF8',
                'year' => 2024,
                'color' => 'Đen Kim Cương',
                'customer_id' => 1,
                'purchase_date' => '2024-01-15',
                'warranty_start_date' => '2024-01-15',
                'warranty_end_date' => '2026-01-15',
                'status' => 'active',
                'mileage' => 5000,
                'battery_capacity' => '87.7 kWh',
                'motor_power' => '300 kW'
            ],
            [
                'id' => 2,
                'vin' => 'VF3GHIJKL87654321',
                'model' => 'VinFast VF9',
                'year' => 2024,
                'color' => 'Trắng Ngọc Trai',
                'customer_id' => 2,
                'purchase_date' => '2024-02-20',
                'warranty_start_date' => '2024-02-20',
                'warranty_end_date' => '2026-02-20',
                'status' => 'active',
                'mileage' => 3000,
                'battery_capacity' => '123 kWh',
                'motor_power' => '300 kW'
            ],
            [
                'id' => 3,
                'vin' => 'VF3MNOPQR98765432',
                'model' => 'VinFast VF5',
                'year' => 2024,
                'color' => 'Xanh Dương',
                'customer_id' => 1,
                'purchase_date' => '2024-03-10',
                'warranty_start_date' => '2024-03-10',
                'warranty_end_date' => '2026-03-10',
                'status' => 'active',
                'mileage' => 2500,
                'battery_capacity' => '62.5 kWh',
                'motor_power' => '150 kW'
            ]
        ];
    }

    public static function getWarrantyClaims() {
        return [
            [
                'id' => 1,
                'claim_number' => 'WC-2024-000001',
                'customer_id' => 1,
                'vehicle_vin' => 'VF3ABCDEF12345678',
                'description' => 'Pin không sạc được',
                'issue_type' => 'battery',
                'priority' => 'high',
                'status' => 'pending',
                'created_at' => '2024-10-01T10:00:00Z',
                'estimated_cost' => 0,
                'technician_id' => null,
                'service_center_id' => 1
            ],
            [
                'id' => 2,
                'claim_number' => 'WC-2024-000002',
                'customer_id' => 2,
                'vehicle_vin' => 'VF3GHIJKL87654321',
                'description' => 'Hệ thống điều hòa không hoạt động',
                'issue_type' => 'electrical',
                'priority' => 'medium',
                'status' => 'in_progress',
                'created_at' => '2024-10-02T14:30:00Z',
                'estimated_cost' => 500000,
                'technician_id' => 5,
                'service_center_id' => 1
            ],
            [
                'id' => 3,
                'claim_number' => 'WC-2025-000003',
                'customer_id' => 1,
                'vehicle_vin' => 'VF3ABCDEF12345678',
                'description' => 'Pin khong sac duoc',
                'issue_type' => 'battery',
                'priority' => 'high',
                'status' => 'pending',
                'created_at' => '2025-10-07T18:20:52+00:00',
                'estimated_cost' => 0,
                'technician_id' => null,
                'service_center_id' => 1
            ]
        ];
    }

    public static function getServiceCenters() {
        return [
            [
                'id' => 1,
                'name' => 'VinFast Service Center Hà Nội',
                'address' => '123 Đường ABC, Hà Nội',
                'phone' => '024-1234567',
                'email' => 'hanoi@vinfast.vn',
                'manager_name' => 'Nguyễn Quản Lý',
                'status' => 'active',
                'region' => 'North'
            ],
            [
                'id' => 2,
                'name' => 'VinFast Service Center TP.HCM',
                'address' => '456 Đường XYZ, TP.HCM',
                'phone' => '028-7654321',
                'email' => 'hcm@vinfast.vn',
                'manager_name' => 'Trần Quản Lý',
                'status' => 'active',
                'region' => 'South'
            ]
        ];
    }

    public static function getTechnicians() {
        return [
            [
                'id' => 5,
                'name' => 'Phạm Kỹ Thuật',
                'email' => 'tech1@vinfast.vn',
                'phone' => '0933333333',
                'service_center_id' => 1,
                'specialization' => 'battery',
                'experience_years' => 5,
                'status' => 'active',
                'role' => 'technician'
            ],
            [
                'id' => 6,
                'name' => 'Lê Sửa Chữa',
                'email' => 'tech2@vinfast.vn',
                'phone' => '0944444444',
                'service_center_id' => 1,
                'specialization' => 'electrical',
                'experience_years' => 3,
                'status' => 'active',
                'role' => 'technician'
            ]
        ];
    }

    public static function getParts() {
        return [
            [
                'id' => 1,
                'part_number' => 'BAT-VF8-001',
                'name' => 'Pin VF8 - Module chính',
                'description' => 'Module pin chính cho VinFast VF8',
                'category' => 'battery',
                'price' => 50000000,
                'warranty_months' => 96,
                'compatible_models' => ['VinFast VF8']
            ],
            [
                'id' => 2,
                'part_number' => 'AC-VF-002',
                'name' => 'Máy nén điều hòa',
                'description' => 'Máy nén điều hòa cho xe VinFast',
                'category' => 'electrical',
                'price' => 15000000,
                'warranty_months' => 24,
                'compatible_models' => ['VinFast VF8', 'VinFast VF9']
            ]
        ];
    }

    public static function getInventory() {
        return [
            [
                'id' => 1,
                'service_center_id' => 1,
                'part_id' => 1,
                'quantity' => 5,
                'min_quantity' => 2,
                'max_quantity' => 10,
                'last_updated' => '2024-10-07T10:00:00Z'
            ],
            [
                'id' => 2,
                'service_center_id' => 1,
                'part_id' => 2,
                'quantity' => 8,
                'min_quantity' => 3,
                'max_quantity' => 15,
                'last_updated' => '2024-10-07T10:00:00Z'
            ]
        ];
    }

    // Utility methods
    public static function getCustomerById($id) {
        $customers = self::getCustomers();
        foreach ($customers as $customer) {
            if ($customer['id'] == $id) {
                return $customer;
            }
        }
        return null;
    }

    public static function getVehicleByVin($vin) {
        $vehicles = self::getVehicles();
        foreach ($vehicles as $vehicle) {
            if ($vehicle['vin'] === $vin) {
                return $vehicle;
            }
        }
        return null;
    }

    public static function getClaimsByCustomerId($customerId) {
        $claims = self::getWarrantyClaims();
        return array_filter($claims, function($claim) use ($customerId) {
            return $claim['customer_id'] == $customerId;
        });
    }

    public static function getVehiclesByCustomerId($customerId) {
        $vehicles = self::getVehicles();
        return array_filter($vehicles, function($vehicle) use ($customerId) {
            return $vehicle['customer_id'] == $customerId;
        });
    }
}
?>