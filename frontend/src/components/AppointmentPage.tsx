import React, { useState } from 'react';
import AppointmentCalendar from './AppointmentCalendar';
import AppointmentBooking from './AppointmentBooking';
import {
  CalendarDaysIcon,
  PlusIcon,
  UserIcon,
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/react/24/outline';

interface Appointment {
  id: number;
  customer_id: number;
  vehicle_vin: string;
  service_center_id: number;
  title: string;
  description?: string;
  type: 'maintenance' | 'repair' | 'warranty' | 'inspection' | 'consultation';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  appointment_date: string;
  start_time: string;
  end_time: string;
  status: 'scheduled' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled';
  customer_name?: string;
  vehicle_model?: string;
  service_center_name?: string;
  technician_name?: string;
}

interface AppointmentPageProps {
  serviceCenterId?: number;
  technicianId?: number;
  userRole?: 'customer' | 'technician' | 'sc_staff' | 'admin';
}

export default function AppointmentPage({
  serviceCenterId,
  technicianId,
  userRole = 'sc_staff'
}: AppointmentPageProps) {
  const [showBookingModal, setShowBookingModal] = useState(false);
  const [selectedAppointment, setSelectedAppointment] = useState<Appointment | null>(null);
  const [showAppointmentDetails, setShowAppointmentDetails] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  const handleAppointmentClick = (appointment: Appointment) => {
    setSelectedAppointment(appointment);
    setShowAppointmentDetails(true);
  };

  const handleBookingSuccess = (appointment: any) => {
    // Refresh calendar data
    setRefreshKey(prev => prev + 1);
    
    // Show success notification
    alert('Đặt lịch hẹn thành công!');
  };

  const handleAppointmentUpdate = async (appointmentId: number, status: string) => {
    try {
      const response = await fetch(`http://localhost:8005/api/appointments/${appointmentId}/status`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status })
      });

      const data = await response.json();

      if (data.success) {
        setRefreshKey(prev => prev + 1);
        setShowAppointmentDetails(false);
        setSelectedAppointment(null);
        alert('Cập nhật trạng thái thành công!');
      }
    } catch (error) {
      console.error('Failed to update appointment:', error);
      alert('Có lỗi xảy ra khi cập nhật trạng thái');
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'scheduled':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'confirmed':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'in_progress':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'completed':
        return 'bg-gray-100 text-gray-800 border-gray-200';
      case 'cancelled':
        return 'bg-red-100 text-red-800 border-red-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'scheduled':
        return 'Đã đặt lịch';
      case 'confirmed':
        return 'Đã xác nhận';
      case 'in_progress':
        return 'Đang thực hiện';
      case 'completed':
        return 'Hoàn thành';
      case 'cancelled':
        return 'Đã hủy';
      default:
        return status;
    }
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'low':
        return 'text-green-600';
      case 'medium':
        return 'text-yellow-600';
      case 'high':
        return 'text-orange-600';
      case 'urgent':
        return 'text-red-600';
      default:
        return 'text-gray-600';
    }
  };

  const getPriorityText = (priority: string) => {
    switch (priority) {
      case 'low':
        return 'Thấp';
      case 'medium':
        return 'Trung bình';
      case 'high':
        return 'Cao';
      case 'urgent':
        return 'Khẩn cấp';
      default:
        return priority;
    }
  };

  const getTypeText = (type: string) => {
    switch (type) {
      case 'maintenance':
        return 'Bảo dưỡng';
      case 'repair':
        return 'Sửa chữa';
      case 'warranty':
        return 'Bảo hành';
      case 'inspection':
        return 'Kiểm tra';
      case 'consultation':
        return 'Tư vấn';
      default:
        return type;
    }
  };

  const canUpdateStatus = (status: string) => {
    if (userRole === 'customer') return false;
    return ['scheduled', 'confirmed', 'in_progress'].includes(status);
  };

  const getAvailableStatuses = (currentStatus: string) => {
    switch (currentStatus) {
      case 'scheduled':
        return ['confirmed', 'cancelled'];
      case 'confirmed':
        return ['in_progress', 'cancelled'];
      case 'in_progress':
        return ['completed', 'cancelled'];
      default:
        return [];
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold flex items-center">
            <CalendarDaysIcon className="h-8 w-8 mr-3 text-blue-600" />
            Quản lý lịch hẹn
          </h1>
          
          {(userRole === 'sc_staff' || userRole === 'admin') && (
            <button
              onClick={() => setShowBookingModal(true)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2"
            >
              <PlusIcon className="h-5 w-5" />
              <span>Đặt lịch hẹn mới</span>
            </button>
          )}
        </div>
      </div>

      {/* Calendar */}
      <AppointmentCalendar
        key={refreshKey}
        serviceCenterId={serviceCenterId}
        technicianId={technicianId}
        onAppointmentClick={handleAppointmentClick}
      />

      {/* Booking Modal */}
      <AppointmentBooking
        isOpen={showBookingModal}
        onClose={() => setShowBookingModal(false)}
        onSuccess={handleBookingSuccess}
        serviceCenterId={serviceCenterId}
      />

      {/* Appointment Details Modal */}
      {showAppointmentDetails && selectedAppointment && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full m-4 max-h-[90vh] overflow-hidden">
            {/* Header */}
            <div className="flex items-center justify-between p-6 border-b">
              <h3 className="text-xl font-semibold">Chi tiết lịch hẹn</h3>
              <button
                onClick={() => setShowAppointmentDetails(false)}
                className="p-2 hover:bg-gray-100 rounded"
              >
                <XCircleIcon className="h-6 w-6" />
              </button>
            </div>

            {/* Content */}
            <div className="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
              <div className="space-y-6">
                {/* Basic Info */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <h4 className="text-lg font-medium mb-4">Thông tin cuộc hẹn</h4>
                    <div className="space-y-3">
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Tiêu đề</label>
                        <p className="text-sm text-gray-900">{selectedAppointment.title}</p>
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Loại dịch vụ</label>
                        <p className="text-sm text-gray-900">{getTypeText(selectedAppointment.type)}</p>
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Mức độ ưu tiên</label>
                        <p className={`text-sm font-medium ${getPriorityColor(selectedAppointment.priority)}`}>
                          {getPriorityText(selectedAppointment.priority)}
                        </p>
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Trạng thái</label>
                        <span className={`inline-block px-2 py-1 text-xs font-medium rounded border ${
                          getStatusColor(selectedAppointment.status)
                        }`}>
                          {getStatusText(selectedAppointment.status)}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div>
                    <h4 className="text-lg font-medium mb-4">Thời gian & Địa điểm</h4>
                    <div className="space-y-3">
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Ngày hẹn</label>
                        <p className="text-sm text-gray-900 flex items-center">
                          <CalendarDaysIcon className="h-4 w-4 mr-2" />
                          {new Date(selectedAppointment.appointment_date).toLocaleDateString('vi-VN')}
                        </p>
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700">Thời gian</label>
                        <p className="text-sm text-gray-900 flex items-center">
                          <ClockIcon className="h-4 w-4 mr-2" />
                          {selectedAppointment.start_time} - {selectedAppointment.end_time}
                        </p>
                      </div>
                      
                      {selectedAppointment.service_center_name && (
                        <div>
                          <label className="block text-sm font-medium text-gray-700">Trung tâm dịch vụ</label>
                          <p className="text-sm text-gray-900">{selectedAppointment.service_center_name}</p>
                        </div>
                      )}
                      
                      {selectedAppointment.technician_name && (
                        <div>
                          <label className="block text-sm font-medium text-gray-700">Kỹ thuật viên</label>
                          <p className="text-sm text-gray-900">{selectedAppointment.technician_name}</p>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Customer & Vehicle Info */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <h4 className="text-lg font-medium mb-4">Thông tin khách hàng</h4>
                    <div className="space-y-3">
                      {selectedAppointment.customer_name && (
                        <div>
                          <label className="block text-sm font-medium text-gray-700">Tên khách hàng</label>
                          <p className="text-sm text-gray-900 flex items-center">
                            <UserIcon className="h-4 w-4 mr-2" />
                            {selectedAppointment.customer_name}
                          </p>
                        </div>
                      )}
                    </div>
                  </div>

                  <div>
                    <h4 className="text-lg font-medium mb-4">Thông tin phương tiện</h4>
                    <div className="space-y-3">
                      <div>
                        <label className="block text-sm font-medium text-gray-700">VIN</label>
                        <p className="text-sm text-gray-900 font-mono">{selectedAppointment.vehicle_vin}</p>
                      </div>
                      
                      {selectedAppointment.vehicle_model && (
                        <div>
                          <label className="block text-sm font-medium text-gray-700">Mẫu xe</label>
                          <p className="text-sm text-gray-900">{selectedAppointment.vehicle_model}</p>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Description */}
                {selectedAppointment.description && (
                  <div>
                    <h4 className="text-lg font-medium mb-4">Mô tả chi tiết</h4>
                    <p className="text-sm text-gray-700 bg-gray-50 p-4 rounded-lg">
                      {selectedAppointment.description}
                    </p>
                  </div>
                )}

                {/* Status Update Actions */}
                {canUpdateStatus(selectedAppointment.status) && (
                  <div>
                    <h4 className="text-lg font-medium mb-4">Cập nhật trạng thái</h4>
                    <div className="flex space-x-3">
                      {getAvailableStatuses(selectedAppointment.status).map(status => (
                        <button
                          key={status}
                          onClick={() => handleAppointmentUpdate(selectedAppointment.id, status)}
                          className={`px-4 py-2 rounded-lg flex items-center space-x-2 ${
                            status === 'cancelled'
                              ? 'bg-red-600 text-white hover:bg-red-700'
                              : status === 'completed'
                              ? 'bg-green-600 text-white hover:bg-green-700'
                              : 'bg-blue-600 text-white hover:bg-blue-700'
                          }`}
                        >
                          {status === 'completed' && <CheckCircleIcon className="h-5 w-5" />}
                          {status === 'cancelled' && <XCircleIcon className="h-5 w-5" />}
                          <span>{getStatusText(status)}</span>
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Footer */}
            <div className="flex justify-end p-6 border-t bg-gray-50">
              <button
                onClick={() => setShowAppointmentDetails(false)}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Đóng
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}