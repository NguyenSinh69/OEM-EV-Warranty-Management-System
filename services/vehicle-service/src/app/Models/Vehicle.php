<?php

namespace App\Models;

class Vehicle
{
    private static $vehicles = [];
    private static $idCounter = 1;
    
    public $vin;
    public $model;
    public $year;
    public $customer_id;
    public $partSlots;
    public $created_at;
    
    public function __construct($data)
    {
        $this->vin = $data['vin'];
        $this->model = $data['model'];
        $this->year = $data['year'];
        $this->customer_id = $data['customer_id'] ?? null;
        $this->partSlots = [
            'battery_serial' => $data['partSlots']['battery_serial'] ?? null,
            'motor_serial' => $data['partSlots']['motor_serial'] ?? null
        ];
        $this->created_at = date('c');
    }
    
    /**
     * Validate VIN format
     */
    public static function validateVin($vin)
    {
        // Basic validation: length >= 10 (full VIN validation will be in W6)
        if (strlen($vin) < 10) {
            return false;
        }
        return true;
    }
    
    /**
     * Validate year range
     */
    public static function validateYear($year)
    {
        $currentYear = (int)date('Y');
        return $year >= 2000 && $year <= ($currentYear + 1);
    }
    
    /**
     * Check if VIN already exists
     */
    public static function vinExists($vin)
    {
        foreach (self::$vehicles as $vehicle) {
            if ($vehicle->vin === $vin) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Create a new vehicle
     */
    public static function create($data)
    {
        // Validation
        if (!self::validateVin($data['vin'])) {
            throw new \Exception('VIN must be at least 10 characters long', 400);
        }
        
        if (!self::validateYear($data['year'])) {
            throw new \Exception('Year must be between 2000 and ' . (date('Y') + 1), 400);
        }
        
        if (empty($data['model'])) {
            throw new \Exception('Model is required', 400);
        }
        
        if (self::vinExists($data['vin'])) {
            throw new \Exception('Vehicle with this VIN already exists', 409);
        }
        
        $vehicle = new self($data);
        self::$vehicles[] = $vehicle;
        
        return $vehicle;
    }
    
    /**
     * Find vehicle by VIN
     */
    public static function findByVin($vin)
    {
        foreach (self::$vehicles as $vehicle) {
            if ($vehicle->vin === $vin) {
                return $vehicle;
            }
        }
        return null;
    }
    
    /**
     * Find vehicles by customer ID
     */
    public static function findByCustomerId($customerId)
    {
        $result = [];
        foreach (self::$vehicles as $vehicle) {
            if ($vehicle->customer_id === $customerId) {
                $result[] = $vehicle;
            }
        }
        return $result;
    }
    
    /**
     * Get all vehicles
     */
    public static function all()
    {
        return self::$vehicles;
    }
    
    /**
     * Update vehicle
     */
    public function update($data)
    {
        if (isset($data['model'])) {
            $this->model = $data['model'];
        }
        
        if (isset($data['year'])) {
            if (!self::validateYear($data['year'])) {
                throw new \Exception('Year must be between 2000 and ' . (date('Y') + 1), 400);
            }
            $this->year = $data['year'];
        }
        
        if (isset($data['customer_id'])) {
            $this->customer_id = $data['customer_id'];
        }
        
        // Don't overwrite existing partSlots
        if (isset($data['partSlots'])) {
            foreach ($data['partSlots'] as $key => $value) {
                if (isset($this->partSlots[$key]) && $value !== null) {
                    $this->partSlots[$key] = $value;
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Convert to array
     */
    public function toArray()
    {
        return [
            'vin' => $this->vin,
            'model' => $this->model,
            'year' => $this->year,
            'customer_id' => $this->customer_id,
            'partSlots' => $this->partSlots,
            'created_at' => $this->created_at
        ];
    }
    
    /**
     * Initialize with some mock data for testing
     */
    public static function initializeMockData()
    {
        if (empty(self::$vehicles)) {
            $mockVehicles = [
                [
                    'vin' => 'VF3ABCDEF12345678',
                    'model' => 'VinFast VF8',
                    'year' => 2024,
                    'customer_id' => 'cust-1',
                    'partSlots' => [
                        'battery_serial' => 'BAT123456',
                        'motor_serial' => null
                    ]
                ],
                [
                    'vin' => 'VF3GHIJKL87654321',
                    'model' => 'VinFast VF9',
                    'year' => 2024,
                    'customer_id' => 'cust-2',
                    'partSlots' => [
                        'battery_serial' => null,
                        'motor_serial' => 'MOT789012'
                    ]
                ]
            ];
            
            foreach ($mockVehicles as $data) {
                self::$vehicles[] = new self($data);
            }
        }
    }
}