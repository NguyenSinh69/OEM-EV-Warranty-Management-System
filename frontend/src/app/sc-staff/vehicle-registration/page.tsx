'use client';

import { useState, useEffect } from 'react';
import {
  TruckIcon,
  UserIcon,
  CalendarIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/react/24/outline';

interface VehicleRegistrationForm {
  vin: string;
  model_id: string;
  year: string;
  color: string;
  customer_id: string;
  purchase_date: string;
  warranty_start_date: string;
  license_plate: string;
}

interface Model {
  id: string;
  name: string;
  full_name: string;
}

interface Customer {
  id: string;
  full_name: string;
  phone: string;
  email: string;
}

export default function VehicleRegistrationPage() {
  const [loading, setLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  
  const [form, setForm] = useState<VehicleRegistrationForm>({
    vin: '',
    model_id: '',
    year: '',
    color: '',
    customer_id: '',
    purchase_date: '',
    warranty_start_date: '',
    license_plate: ''
  });

  const [models, setModels] = useState<Model[]>([]);
  const [customers, setCustomers] = useState<Customer[]>([]);

  // Set default dates after mount to avoid hydration mismatch
  useEffect(() => {
    const today = new Date().toISOString().split('T')[0];
    const currentYear = new Date().getFullYear().toString();
    setForm(prev => ({
      ...prev,
      year: currentYear,
      purchase_date: today,
      warranty_start_date: today
    }));
  }, []);

  // Load reference data
  useEffect(() => {
    loadReferenceData();
  }, []);

  const loadReferenceData = async () => {
    try {
      // Load models
      const modelsResponse = await fetch('http://localhost:8003/api/sc-staff/reference-data');
      if (modelsResponse.ok) {
        const modelsData = await modelsResponse.json();
        if (modelsData.success) {
          setModels(modelsData.data.models || []);
          setCustomers(modelsData.data.customers || []);
        }
      } else {
        // Mock data if API fails
        setModels([
          { id: '1', name: 'VF8', full_name: 'VinFast VF8 Eco' },
          { id: '2', name: 'VF9', full_name: 'VinFast VF9 Plus' },
          { id: '3', name: 'VFe34', full_name: 'VinFast VFe34' }
        ]);
        setCustomers([
          { id: '1', full_name: 'Nguyễn Văn An', phone: '0901234567', email: 'nvana@gmail.com' },
          { id: '2', full_name: 'Trần Thị Bình', phone: '0912345678', email: 'ttbinh@gmail.com' },
          { id: '3', full_name: 'Lê Văn Cường', phone: '0923456789', email: 'lvcuong@gmail.com' }
        ]);
      }
    } catch (error) {
      console.error('Failed to load reference data:', error);
      // Use mock data
      setModels([
        { id: '1', name: 'VF8', full_name: 'VinFast VF8 Eco' },
        { id: '2', name: 'VF9', full_name: 'VinFast VF9 Plus' },
        { id: '3', name: 'VFe34', full_name: 'VinFast VFe34' }
      ]);
      setCustomers([
        { id: '1', full_name: 'Nguyễn Văn An', phone: '0901234567', email: 'nvana@gmail.com' },
        { id: '2', full_name: 'Trần Thị Bình', phone: '0912345678', email: 'ttbinh@gmail.com' }
      ]);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrorMessage('');
    setSuccessMessage('');

    try {
      const response = await fetch('http://localhost:8003/api/sc-staff/vehicles/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(form)
      });

      const data = await response.json();
      
      if (data.success) {
        setSuccessMessage(`Đăng ký xe thành công! Số VIN: ${form.vin}`);
        // Reset form
        const today = new Date().toISOString().split('T')[0];
        const currentYear = new Date().getFullYear().toString();
        setForm({
          vin: '',
          model_id: '',
          year: currentYear,
          color: '',
          customer_id: '',
          purchase_date: today,
          warranty_start_date: today,
          license_plate: ''
        });
      } else {
        setErrorMessage(data.error || 'Đăng ký thất bại');
      }
    } catch (error) {
      setErrorMessage('Lỗi mạng. Vui lòng thử lại.');
      console.error('Registration error:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateForm = (field: keyof VehicleRegistrationForm, value: string) => {
    setForm(prev => ({
      ...prev,
      [field]: value
    }));
  };

  return (
    <div className="p-6">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center mb-2">
            <TruckIcon className="h-8 w-8 text-purple-600 mr-3" />
            <h1 className="text-3xl font-bold text-gray-900">Đăng Ký Xe Điện</h1>
          </div>
          <p className="text-gray-600">Đăng ký xe điện mới và thông tin bảo hành</p>
        </div>

        {/* Alert Messages */}
        {successMessage && (
          <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center">
            <CheckCircleIcon className="h-5 w-5 text-green-600 mr-2" />
            <span className="text-green-800">{successMessage}</span>
          </div>
        )}

        {errorMessage && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center">
            <ExclamationTriangleIcon className="h-5 w-5 text-red-600 mr-2" />
            <span className="text-red-800">{errorMessage}</span>
          </div>
        )}

        {/* Registration Form */}
        <div className="bg-white rounded-lg shadow-sm border">
          <form onSubmit={handleSubmit} className="p-6 space-y-6">
            {/* Vehicle Information */}
            <div>
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <TruckIcon className="h-5 w-5 mr-2" />
                Thông Tin Xe
              </h2>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Số VIN *
                  </label>
                  <input
                    type="text"
                    value={form.vin}
                    onChange={(e) => updateForm('vin', e.target.value)}
                    placeholder="VF3ABCDEF12345678"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                    maxLength={17}
                  />
                  <p className="text-xs text-gray-500 mt-1">Số nhận dạng xe gồm 17 ký tự</p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Mẫu Xe *
                  </label>
                  <select
                    value={form.model_id}
                    onChange={(e) => updateForm('model_id', e.target.value)}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  >
                    <option value="">Chọn mẫu xe</option>
                    {models.map((model) => (
                      <option key={model.id} value={model.id}>
                        {model.full_name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Năm Sản Xuất *
                  </label>
                  <input
                    type="number"
                    value={form.year}
                    onChange={(e) => updateForm('year', e.target.value)}
                    min="2020"
                    max="2030"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  />
                </div>

                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                    Màu Sắc *
                  </label>
                  <select
                    value={form.color}
                    onChange={(e) => updateForm('color', e.target.value)}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  >
                    <option value="">Chọn màu sắc</option>
                    <option value="White">Trắng</option>
                    <option value="Black">Đen</option>
                    <option value="Silver">Bạc</option>
                    <option value="Blue">Xanh</option>
                    <option value="Red">Đỏ</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Biển Số Xe
                  </label>
                  <input
                    type="text"
                    value={form.license_plate}
                    onChange={(e) => updateForm('license_plate', e.target.value)}
                    placeholder="30A-12345"
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
              </div>
            </div>

            {/* Customer Information */}
            <div>
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <UserIcon className="h-5 w-5 mr-2" />
                Thông Tin Khách Hàng
              </h2>
              
              <div className="grid grid-cols-1 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Khách Hàng *
                  </label>
                  <select
                    value={form.customer_id}
                    onChange={(e) => updateForm('customer_id', e.target.value)}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  >
                    <option value="">Chọn khách hàng</option>
                    {customers.map((customer) => (
                      <option key={customer.id} value={customer.id}>
                        {customer.full_name} - {customer.phone}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>

            {/* Warranty Information */}
            <div>
              <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <DocumentTextIcon className="h-5 w-5 mr-2" />
                Thông Tin Bảo Hành
              </h2>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Ngày Mua *
                  </label>
                  <input
                    type="date"
                    value={form.purchase_date}
                    onChange={(e) => updateForm('purchase_date', e.target.value)}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Ngày Bắt Đầu Bảo Hành *
                  </label>
                  <input
                    type="date"
                    value={form.warranty_start_date}
                    onChange={(e) => updateForm('warranty_start_date', e.target.value)}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  />
                </div>
              </div>
            </div>

            {/* Submit Button */}
            <div className="flex justify-end pt-4 border-t">
              <button
                type="submit"
                disabled={loading}
                className="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center"
              >
                {loading ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Đang đăng ký...
                  </>
                ) : (
                  <>
                    <CheckCircleIcon className="h-5 w-5 mr-2" />
                    Đăng Ký Xe
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}