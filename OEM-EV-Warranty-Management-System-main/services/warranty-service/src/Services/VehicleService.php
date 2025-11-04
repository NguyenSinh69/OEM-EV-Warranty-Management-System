<?php

// 1. Khai báo Namespace phải khớp với đường dẫn (Services)
namespace Dell\WarrantyService\Services;

// 2. Định nghĩa Class
class VehicleService
{
    // Thêm hàm khởi tạo (constructor) nếu cần
    public function __construct()
    {
        // Có thể để trống hoặc thêm logic khởi tạo
    }

    // 3. Đặt hàm của bạn làm phương thức (method) trong Class
    public function isValidVin(string $vin): bool
    {
        // Giả lập danh sách VIN hợp lệ
        $validVins = ['VIN123', 'VIN456', 'VIN789'];
        return in_array($vin, $validVins);
    }
}