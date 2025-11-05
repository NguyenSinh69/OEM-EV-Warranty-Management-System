<?php

namespace App\Http\Controllers;

class AppointmentController
{
    private $db;
    
    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    /**
     * POST /api/appointments - Đặt lịch hẹn
     */
    public function create()
    {
        try {
            $data = getJsonInput();
            
            // Validate required fields
            $required = [
                'customer_id', 'vehicle_vin', 'service_center_id', 
                'title', 'type', 'appointment_date', 'start_time'
            ];
            $missing = validateRequired($data, $required);
            
            if (!empty($missing)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'missing_fields' => $missing
                ], 422);
            }
            
            // Validate enum values
            $validTypes = ['maintenance', 'repair', 'warranty', 'inspection', 'consultation'];
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            
            if (!in_array($data['type'], $validTypes)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid appointment type',
                    'valid_types' => $validTypes
                ], 422);
            }
            
            $priority = $data['priority'] ?? 'medium';
            if (!in_array($priority, $validPriorities)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid priority',
                    'valid_priorities' => $validPriorities
                ], 422);
            }
            
            // Calculate end time if not provided
            $durationMinutes = $data['duration_minutes'] ?? 60;
            $endTime = $data['end_time'] ?? $this->calculateEndTime($data['start_time'], $durationMinutes);
            
            // Check for time slot conflicts
            if ($this->hasTimeConflict($data['service_center_id'], $data['appointment_date'], $data['start_time'], $endTime)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Time slot not available',
                    'suggestion' => $this->suggestAlternativeSlots($data['service_center_id'], $data['appointment_date'])
                ], 409);
            }
            
            // Create appointment
            $sql = "INSERT INTO appointments (
                customer_id, vehicle_vin, service_center_id, technician_id,
                title, description, type, priority, 
                appointment_date, start_time, end_time, duration_minutes,
                contact_phone, contact_email, customer_notes, estimated_cost,
                created_by, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['customer_id'],
                $data['vehicle_vin'],
                $data['service_center_id'],
                $data['technician_id'] ?? null,
                $data['title'],
                $data['description'] ?? null,
                $data['type'],
                $priority,
                $data['appointment_date'],
                $data['start_time'],
                $endTime,
                $durationMinutes,
                $data['contact_phone'] ?? null,
                $data['contact_email'] ?? null,
                $data['customer_notes'] ?? null,
                $data['estimated_cost'] ?? null,
                $data['created_by'] ?? null,
                'scheduled'
            ]);
            
            $appointmentId = $this->db->lastInsertId();
            
            // Send confirmation notification
            $this->sendAppointmentNotification($appointmentId, 'created');
            
            // Get created appointment with relations
            $appointment = $this->getAppointmentById($appointmentId);
            
            return jsonResponse([
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => $appointment
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Appointment creation error: " . $e->getMessage());
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to create appointment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/appointments/calendar - Lịch appointments
     */
    public function getCalendar()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-d');
            $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
            $serviceCenterId = $_GET['service_center_id'] ?? null;
            $technicianId = $_GET['technician_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            // Build query
            $whereConditions = ['appointment_date BETWEEN ? AND ?'];
            $params = [$startDate, $endDate];
            
            if ($serviceCenterId) {
                $whereConditions[] = 'service_center_id = ?';
                $params[] = $serviceCenterId;
            }
            
            if ($technicianId) {
                $whereConditions[] = 'technician_id = ?';
                $params[] = $technicianId;
            }
            
            if ($status) {
                $whereConditions[] = 'status = ?';
                $params[] = $status;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "SELECT 
                a.*,
                'Customer Name' as customer_name,
                'VF8' as vehicle_model,
                'Service Center A' as service_center_name,
                'Tech A' as technician_name
            FROM appointments a 
            WHERE $whereClause 
            ORDER BY appointment_date, start_time";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $appointments = $stmt->fetchAll();
            
            // Group by date for calendar view
            $calendar = [];
            foreach ($appointments as $appointment) {
                $date = $appointment['appointment_date'];
                if (!isset($calendar[$date])) {
                    $calendar[$date] = [];
                }
                $calendar[$date][] = $appointment;
            }
            
            // Get statistics
            $stats = $this->getCalendarStats($startDate, $endDate, $serviceCenterId);
            
            return jsonResponse([
                'success' => true,
                'data' => [
                    'calendar' => $calendar,
                    'appointments' => $appointments,
                    'stats' => $stats,
                    'date_range' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ],
                'message' => 'Calendar retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to get calendar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/appointments/{id} - Chi tiết appointment
     */
    public function show($id)
    {
        try {
            $appointment = $this->getAppointmentById($id);
            
            if (!$appointment) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }
            
            return jsonResponse([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to get appointment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT /api/appointments/{id} - Cập nhật appointment
     */
    public function update($id)
    {
        try {
            $data = getJsonInput();
            
            // Check if appointment exists
            $appointment = $this->getAppointmentById($id);
            if (!$appointment) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }
            
            // Build update query dynamically
            $updateFields = [];
            $params = [];
            
            $allowedFields = [
                'technician_id', 'title', 'description', 'priority',
                'appointment_date', 'start_time', 'end_time', 'duration_minutes',
                'contact_phone', 'contact_email', 'customer_notes', 
                'technician_notes', 'completion_notes', 'estimated_cost', 
                'actual_cost', 'status'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ], 422);
            }
            
            // Add updated_at
            $updateFields[] = 'updated_at = NOW()';
            
            // Handle status changes
            if (isset($data['status'])) {
                $this->handleStatusChange($id, $appointment['status'], $data['status'], $data);
            }
            
            $params[] = $id;
            
            $sql = "UPDATE appointments SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            // Send notification if status changed
            if (isset($data['status']) && $data['status'] !== $appointment['status']) {
                $this->sendAppointmentNotification($id, 'status_changed', $data['status']);
            }
            
            // Get updated appointment
            $updatedAppointment = $this->getAppointmentById($id);
            
            return jsonResponse([
                'success' => true,
                'message' => 'Appointment updated successfully',
                'data' => $updatedAppointment
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to update appointment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper methods
     */
    private function calculateEndTime($startTime, $durationMinutes)
    {
        $start = new \DateTime($startTime);
        $start->add(new \DateInterval("PT{$durationMinutes}M"));
        return $start->format('H:i:s');
    }
    
    private function hasTimeConflict($serviceCenterId, $date, $startTime, $endTime)
    {
        $sql = "SELECT COUNT(*) as conflicts 
                FROM appointments 
                WHERE service_center_id = ? 
                AND appointment_date = ? 
                AND status NOT IN ('cancelled', 'completed')
                AND (
                    (start_time <= ? AND end_time > ?) OR
                    (start_time < ? AND end_time >= ?) OR
                    (start_time >= ? AND end_time <= ?)
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $serviceCenterId, $date, 
            $startTime, $startTime,
            $endTime, $endTime,
            $startTime, $endTime
        ]);
        
        $result = $stmt->fetch();
        return $result['conflicts'] > 0;
    }
    
    private function suggestAlternativeSlots($serviceCenterId, $date)
    {
        // Simple suggestion logic - find available 1-hour slots
        $slots = [];
        $workStart = '08:00:00';
        $workEnd = '17:00:00';
        
        for ($hour = 8; $hour < 17; $hour++) {
            $slotStart = sprintf('%02d:00:00', $hour);
            $slotEnd = sprintf('%02d:00:00', $hour + 1);
            
            if (!$this->hasTimeConflict($serviceCenterId, $date, $slotStart, $slotEnd)) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd
                ];
            }
        }
        
        return array_slice($slots, 0, 3); // Return first 3 available slots
    }
    
    private function getAppointmentById($id)
    {
        $sql = "SELECT a.*,
                'Customer Name' as customer_name,
                'customer@example.com' as customer_email,
                'VF8' as vehicle_model,
                'Service Center A' as service_center_name,
                'Tech A' as technician_name
                FROM appointments a WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    private function getCalendarStats($startDate, $endDate, $serviceCenterId = null)
    {
        $whereConditions = ['appointment_date BETWEEN ? AND ?'];
        $params = [$startDate, $endDate];
        
        if ($serviceCenterId) {
            $whereConditions[] = 'service_center_id = ?';
            $params[] = $serviceCenterId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    COUNT(*) as total_appointments,
                    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM appointments 
                WHERE $whereClause";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    private function handleStatusChange($appointmentId, $oldStatus, $newStatus, $data)
    {
        switch ($newStatus) {
            case 'confirmed':
                $sql = "UPDATE appointments SET confirmed_at = NOW(), confirmed_by = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['confirmed_by'] ?? null, $appointmentId]);
                break;
                
            case 'completed':
                $sql = "UPDATE appointments SET completed_at = NOW(), completed_by = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['completed_by'] ?? null, $appointmentId]);
                break;
        }
    }
    
    private function sendAppointmentNotification($appointmentId, $type, $newStatus = null)
    {
        // This would integrate with the notification system
        // For now, just log the action
        error_log("Appointment notification: {$type} for appointment {$appointmentId}");
    }
}