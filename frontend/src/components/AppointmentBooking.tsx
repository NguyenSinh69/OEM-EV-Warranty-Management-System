import React, { useState, useEffect } from 'react';
import { 
  CalendarDaysIcon,
  ClockIcon,
  XMarkIcon,
  UserIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
  ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

interface TimeSlot {
  time: string;
  available: boolean;
  technician_id?: number;
  technician_name?: string;
}

interface ServiceCenter {
  id: number;
  name: string;
  address: string;
  phone: string;
}

interface Customer {
  id: number;
  name: string;
  email: string;
  phone: string;
}

interface Vehicle {
  vin: string;
  make: string;
  model: string;
  year: number;
  color: string;
}

interface AppointmentBookingProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess?: (appointment: any) => void;
  customerId?: number;
  vehicleVin?: string;
  serviceCenterId?: number;
}

export default function AppointmentBooking({
  isOpen,
  onClose,
  onSuccess,
  customerId,
  vehicleVin,
  serviceCenterId
}: AppointmentBookingProps) {
  const [currentStep, setCurrentStep] = useState(1);
  const [loading, setLoading] = useState(false);
  
  // Form data
  const [formData, setFormData] = useState({
    customer_id: customerId || 0,
    vehicle_vin: vehicleVin || '',
    service_center_id: serviceCenterId || 0,
    title: '',
    description: '',
    type: 'maintenance' as 'maintenance' | 'repair' | 'warranty' | 'inspection' | 'consultation',
    priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
    appointment_date: '',
    start_time: '',
    end_time: '',
    technician_id: 0
  });

  // Options data
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [serviceCenters, setServiceCenters] = useState<ServiceCenter[]>([]);
  const [timeSlots, setTimeSlots] = useState<TimeSlot[]>([]);
  const [errors, setErrors] = useState<any>({});

  useEffect(() => {
    if (isOpen) {
      fetchInitialData();
    }
  }, [isOpen]);

  useEffect(() => {
    if (formData.service_center_id && formData.appointment_date) {
      fetchTimeSlots();
    }
  }, [formData.service_center_id, formData.appointment_date]);

  const fetchInitialData = async () => {
    try {
      const [customersRes, centersRes] = await Promise.all([
        fetch('http://localhost:8002/api/customers'),
        fetch('http://localhost:8005/api/service-centers')
      ]);

      const [customersData, centersData] = await Promise.all([
        customersRes.json(),
        centersRes.json()
      ]);

      if (customersData.success) setCustomers(customersData.data);
      if (centersData.success) setServiceCenters(centersData.data);

      if (formData.customer_id) {
        fetchCustomerVehicles(formData.customer_id);
      }
    } catch (error) {
      console.error('Failed to fetch initial data:', error);
    }
  };

  const fetchCustomerVehicles = async (customerId: number) => {
    try {
      const response = await fetch(`http://localhost:8003/api/vehicles/customer/${customerId}`);
      const data = await response.json();
      
      if (data.success) {
        setVehicles(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch vehicles:', error);
    }
  };

  const fetchTimeSlots = async () => {
    try {
      const response = await fetch(
        `http://localhost:8005/api/appointments/available-slots?service_center_id=${formData.service_center_id}&date=${formData.appointment_date}`
      );
      const data = await response.json();
      
      if (data.success) {
        setTimeSlots(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch time slots:', error);
    }
  };

  const handleInputChange = (field: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
    
    // Clear error when user starts typing
    if (errors[field]) {
      setErrors((prev: any) => ({
        ...prev,
        [field]: ''
      }));
    }

    // Fetch vehicles when customer changes
    if (field === 'customer_id' && value) {
      fetchCustomerVehicles(value);
    }
  };

  const validateStep = (step: number) => {
    const newErrors: any = {};

    switch (step) {
      case 1:
        if (!formData.customer_id) newErrors.customer_id = 'Vui lòng chọn khách hàng';
        if (!formData.vehicle_vin) newErrors.vehicle_vin = 'Vui lòng chọn phương tiện';
        if (!formData.service_center_id) newErrors.service_center_id = 'Vui lòng chọn trung tâm dịch vụ';
        break;
        
      case 2:
        if (!formData.title) newErrors.title = 'Vui lòng nhập tiêu đề cuộc hẹn';
        if (!formData.type) newErrors.type = 'Vui lòng chọn loại dịch vụ';
        break;
        
      case 3:
        if (!formData.appointment_date) newErrors.appointment_date = 'Vui lòng chọn ngày hẹn';
        if (!formData.start_time) newErrors.start_time = 'Vui lòng chọn giờ hẹn';
        break;
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(prev => prev + 1);
    }
  };

  const handleBack = () => {
    setCurrentStep(prev => prev - 1);
  };

  const handleSubmit = async () => {
    if (!validateStep(3)) return;

    try {
      setLoading(true);
      
      const response = await fetch('http://localhost:8005/api/appointments', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();

      if (data.success) {
        onSuccess?.(data.data);
        onClose();
        // Reset form
        setFormData({
          customer_id: customerId || 0,
          vehicle_vin: vehicleVin || '',
          service_center_id: serviceCenterId || 0,
          title: '',
          description: '',
          type: 'maintenance',
          priority: 'medium',
          appointment_date: '',
          start_time: '',
          end_time: '',
          technician_id: 0
        });
        setCurrentStep(1);
      } else {
        setErrors({ submit: data.message || 'Có lỗi xảy ra' });
      }
    } catch (error) {
      setErrors({ submit: 'Không thể kết nối đến server' });
    } finally {
      setLoading(false);
    }
  };

  const handleTimeSlotSelect = (slot: TimeSlot) => {
    if (!slot.available) return;
    
    const startTime = slot.time;
    const endTime = calculateEndTime(startTime, formData.type);
    
    setFormData(prev => ({
      ...prev,
      start_time: startTime,
      end_time: endTime,
      technician_id: slot.technician_id || 0
    }));
  };

  const calculateEndTime = (startTime: string, type: string) => {
    const [hours, minutes] = startTime.split(':').map(Number);
    const start = new Date();
    start.setHours(hours, minutes, 0, 0);
    
    // Duration based on service type
    const durations = {
      maintenance: 60,
      repair: 120,
      warranty: 90,
      inspection: 45,
      consultation: 30
    };
    
    const duration = durations[type as keyof typeof durations] || 60;
    start.setMinutes(start.getMinutes() + duration);
    
    return start.toTimeString().slice(0, 5);
  };

  const renderStep1 = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-medium">Thông tin cơ bản</h3>
      
      {/* Customer selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Khách hàng *
        </label>
        <select
          value={formData.customer_id}
          onChange={(e) => handleInputChange('customer_id', parseInt(e.target.value))}
          className={`w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
            errors.customer_id ? 'border-red-500' : 'border-gray-300'
          }`}
          disabled={!!customerId}
        >
          <option value={0}>Chọn khách hàng</option>
          {customers.map(customer => (
            <option key={customer.id} value={customer.id}>
              {customer.name} - {customer.email}
            </option>
          ))}
        </select>
        {errors.customer_id && (
          <p className="text-red-500 text-sm mt-1">{errors.customer_id}</p>
        )}
      </div>

      {/* Vehicle selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Phương tiện *
        </label>
        <select
          value={formData.vehicle_vin}
          onChange={(e) => handleInputChange('vehicle_vin', e.target.value)}
          className={`w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
            errors.vehicle_vin ? 'border-red-500' : 'border-gray-300'
          }`}
          disabled={!formData.customer_id || !!vehicleVin}
        >
          <option value="">Chọn phương tiện</option>
          {vehicles.map(vehicle => (
            <option key={vehicle.vin} value={vehicle.vin}>
              {vehicle.make} {vehicle.model} {vehicle.year} - {vehicle.vin}
            </option>
          ))}
        </select>
        {errors.vehicle_vin && (
          <p className="text-red-500 text-sm mt-1">{errors.vehicle_vin}</p>
        )}
      </div>

      {/* Service center selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Trung tâm dịch vụ *
        </label>
        <select
          value={formData.service_center_id}
          onChange={(e) => handleInputChange('service_center_id', parseInt(e.target.value))}
          className={`w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
            errors.service_center_id ? 'border-red-500' : 'border-gray-300'
          }`}
          disabled={!!serviceCenterId}
        >
          <option value={0}>Chọn trung tâm dịch vụ</option>
          {serviceCenters.map(center => (
            <option key={center.id} value={center.id}>
              {center.name} - {center.address}
            </option>
          ))}
        </select>
        {errors.service_center_id && (
          <p className="text-red-500 text-sm mt-1">{errors.service_center_id}</p>
        )}
      </div>
    </div>
  );

  const renderStep2 = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-medium">Chi tiết dịch vụ</h3>
      
      {/* Title */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Tiêu đề cuộc hẹn *
        </label>
        <input
          type="text"
          value={formData.title}
          onChange={(e) => handleInputChange('title', e.target.value)}
          className={`w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
            errors.title ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="Ví dụ: Bảo dưỡng định kỳ"
        />
        {errors.title && (
          <p className="text-red-500 text-sm mt-1">{errors.title}</p>
        )}
      </div>

      {/* Service type */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Loại dịch vụ *
        </label>
        <div className="grid grid-cols-2 gap-3">
          {[
            { value: 'maintenance', label: 'Bảo dưỡng', icon: '🔧' },
            { value: 'repair', label: 'Sửa chữa', icon: '🛠️' },
            { value: 'warranty', label: 'Bảo hành', icon: '📋' },
            { value: 'inspection', label: 'Kiểm tra', icon: '🔍' },
            { value: 'consultation', label: 'Tư vấn', icon: '💬' }
          ].map(type => (
            <button
              key={type.value}
              type="button"
              onClick={() => handleInputChange('type', type.value)}
              className={`p-3 border rounded-lg text-left hover:bg-gray-50 ${
                formData.type === type.value
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-300'
              }`}
            >
              <div className="flex items-center space-x-2">
                <span className="text-lg">{type.icon}</span>
                <span className="font-medium">{type.label}</span>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Priority */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Mức độ ưu tiên
        </label>
        <select
          value={formData.priority}
          onChange={(e) => handleInputChange('priority', e.target.value)}
          className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
        >
          <option value="low">Thấp</option>
          <option value="medium">Trung bình</option>
          <option value="high">Cao</option>
          <option value="urgent">Khẩn cấp</option>
        </select>
      </div>

      {/* Description */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Mô tả chi tiết
        </label>
        <textarea
          value={formData.description}
          onChange={(e) => handleInputChange('description', e.target.value)}
          rows={4}
          className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          placeholder="Mô tả chi tiết về dịch vụ cần thực hiện..."
        />
      </div>
    </div>
  );

  const renderStep3 = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-medium">Chọn thời gian</h3>
      
      {/* Date selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Ngày hẹn *
        </label>
        <input
          type="date"
          value={formData.appointment_date}
          onChange={(e) => handleInputChange('appointment_date', e.target.value)}
          min={new Date().toISOString().split('T')[0]}
          className={`w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 ${
            errors.appointment_date ? 'border-red-500' : 'border-gray-300'
          }`}
        />
        {errors.appointment_date && (
          <p className="text-red-500 text-sm mt-1">{errors.appointment_date}</p>
        )}
      </div>

      {/* Time slots */}
      {formData.appointment_date && (
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Giờ hẹn *
          </label>
          <div className="grid grid-cols-3 gap-3 max-h-64 overflow-y-auto">
            {timeSlots.map((slot, index) => (
              <button
                key={index}
                type="button"
                onClick={() => handleTimeSlotSelect(slot)}
                disabled={!slot.available}
                className={`p-3 border rounded-lg text-center ${
                  !slot.available
                    ? 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed'
                    : formData.start_time === slot.time
                    ? 'border-blue-500 bg-blue-50 text-blue-700'
                    : 'border-gray-300 hover:border-blue-300 hover:bg-blue-50'
                }`}
              >
                <div className="font-medium">{slot.time}</div>
                {slot.technician_name && (
                  <div className="text-xs text-gray-500 mt-1">
                    {slot.technician_name}
                  </div>
                )}
              </button>
            ))}
          </div>
          {errors.start_time && (
            <p className="text-red-500 text-sm mt-1">{errors.start_time}</p>
          )}
        </div>
      )}

      {/* Selected time info */}
      {formData.start_time && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 className="font-medium text-blue-900">Thông tin cuộc hẹn</h4>
          <div className="mt-2 space-y-1 text-sm text-blue-700">
            <div>Thời gian: {formData.start_time} - {formData.end_time}</div>
            <div>Loại dịch vụ: {formData.type}</div>
            <div>Ước tính thời gian: {
              (() => {
                const start = new Date(`2000-01-01T${formData.start_time}`);
                const end = new Date(`2000-01-01T${formData.end_time}`);
                const diff = (end.getTime() - start.getTime()) / (1000 * 60);
                return `${diff} phút`;
              })()
            }</div>
          </div>
        </div>
      )}
    </div>
  );

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full m-4 max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <h2 className="text-xl font-semibold flex items-center">
            <CalendarDaysIcon className="h-6 w-6 mr-2" />
            Đặt lịch hẹn
          </h2>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded"
          >
            <XMarkIcon className="h-6 w-6" />
          </button>
        </div>

        {/* Progress indicator */}
        <div className="px-6 py-4 bg-gray-50 border-b">
          <div className="flex items-center space-x-4">
            {[1, 2, 3].map(step => (
              <div key={step} className="flex items-center">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium ${
                  currentStep >= step
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-200 text-gray-600'
                }`}>
                  {step}
                </div>
                <div className={`ml-2 text-sm ${
                  currentStep >= step ? 'text-blue-600' : 'text-gray-500'
                }`}>
                  {step === 1 ? 'Thông tin' : step === 2 ? 'Dịch vụ' : 'Thời gian'}
                </div>
                {step < 3 && (
                  <div className={`w-8 h-0.5 mx-4 ${
                    currentStep > step ? 'bg-blue-600' : 'bg-gray-200'
                  }`} />
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Content */}
        <div className="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
          {currentStep === 1 && renderStep1()}
          {currentStep === 2 && renderStep2()}
          {currentStep === 3 && renderStep3()}

          {errors.submit && (
            <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
              <div className="flex items-center">
                <ExclamationTriangleIcon className="h-5 w-5 text-red-400 mr-2" />
                <span className="text-red-800">{errors.submit}</span>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between p-6 border-t bg-gray-50">
          <button
            onClick={handleBack}
            disabled={currentStep === 1}
            className="px-4 py-2 text-gray-600 hover:text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Quay lại
          </button>

          <div className="flex space-x-3">
            <button
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Hủy bỏ
            </button>
            
            {currentStep < 3 ? (
              <button
                onClick={handleNext}
                className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                Tiếp tục
              </button>
            ) : (
              <button
                onClick={handleSubmit}
                disabled={loading}
                className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
              >
                {loading ? 'Đang xử lý...' : 'Đặt lịch hẹn'}
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}