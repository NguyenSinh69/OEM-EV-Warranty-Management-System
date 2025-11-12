'use client';

import { useState, useEffect } from 'react';
import {
  DocumentTextIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  EyeIcon
} from '@heroicons/react/24/outline';

interface WarrantyClaim {
  id: string;
  claim_number: string;
  vin: string;
  model_name: string;
  customer_name: string;
  issue_description: string;
  priority: string;
  status: string;
  created_at: string;
  failure_date?: string;
}

interface NewClaimForm {
  vehicle_vin: string;
  issue_description: string;
  symptoms: string;
  failure_date: string;
  failure_mileage: string;
  priority: 'low' | 'medium' | 'high' | 'critical';
}

interface Vehicle {
  id: string;
  vin: string;
  model_name: string;
  customer_name: string;
}

export default function ClaimManagementPage() {
  const [activeTab, setActiveTab] = useState<'list' | 'create'>('list');
  const [claims, setClaims] = useState<WarrantyClaim[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);

  const [newClaim, setNewClaim] = useState<NewClaimForm>({
    vehicle_vin: '',
    issue_description: '',
    symptoms: '',
    failure_date: '',
    failure_mileage: '',
    priority: 'medium'
  });

  useEffect(() => {
    // Set default date after mount
    setNewClaim(prev => ({
      ...prev,
      failure_date: new Date().toISOString().split('T')[0]
    }));
    loadClaims();
    loadVehicles();
  }, [statusFilter]);

  const loadVehicles = async () => {
    try {
      const response = await fetch('http://localhost:8003/api/sc-staff/vehicles/search');
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setVehicles(data.data || []);
        }
      }
    } catch (error) {
      console.error('Failed to load vehicles:', error);
    }
  };

  const loadClaims = async () => {
    setLoading(true);
    try {
      const response = await fetch(`http://localhost:8003/api/sc-staff/warranty-claims?status=${statusFilter}`);
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setClaims(data.data || []);
        }
      } else {
        // Mock data
        setClaims([
          {
            id: '1',
            claim_number: 'WC-2024-001',
            vin: 'VF3ABCDEF12345678',
            model_name: 'VF8 Eco',
            customer_name: 'Nguyễn Văn An',
            issue_description: 'Battery showing reduced range capacity',
            priority: 'medium',
            status: 'under_review',
            created_at: '2024-11-05T09:30:00Z',
            failure_date: '2024-11-01'
          },
          {
            id: '2',
            claim_number: 'WC-2024-002',
            vin: 'VF3BCDEFG23456789',
            model_name: 'VF9 Plus',
            customer_name: 'Trần Thị Bình',
            issue_description: 'Charging port not working properly',
            priority: 'high',
            status: 'submitted',
            created_at: '2024-11-04T14:20:00Z',
            failure_date: '2024-11-02'
          }
        ]);
      }
    } catch (error) {
      console.error('Failed to load claims:', error);
      setErrorMessage('Không thể tải danh sách yêu cầu bảo hành');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateClaim = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrorMessage('');
    setSuccessMessage('');

    try {
      // Find vehicle by VIN to get vehicle_id
      const vehicle = vehicles.find(v => v.vin === newClaim.vehicle_vin);
      if (!vehicle) {
        setErrorMessage('Không tìm thấy xe với VIN này');
        setLoading(false);
        return;
      }

      // Prepare data with vehicle_id instead of vehicle_vin
      const claimData = {
        vehicle_id: vehicle.id,
        issue_description: newClaim.issue_description,
        symptoms: newClaim.symptoms,
        failure_date: newClaim.failure_date,
        failure_mileage: newClaim.failure_mileage,
        priority: newClaim.priority
      };

      const response = await fetch('http://localhost:8003/api/sc-staff/warranty-claims/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(claimData)
      });

      const data = await response.json();
      
      if (data.success) {
        setSuccessMessage(`Tạo yêu cầu bảo hành thành công! Số yêu cầu: ${data.claim_number}`);
        setActiveTab('list');
        loadClaims(); // Refresh the list
        // Reset form
        setNewClaim({
          vehicle_vin: '',
          issue_description: '',
          symptoms: '',
          failure_date: new Date().toISOString().split('T')[0],
          failure_mileage: '',
          priority: 'medium'
        });
      } else {
        setErrorMessage(data.error || 'Không thể tạo yêu cầu bảo hành');
      }
    } catch (error) {
      setErrorMessage('Lỗi mạng. Vui lòng thử lại.');
      console.error('Create claim error:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'draft':
        return <DocumentTextIcon className="h-5 w-5 text-gray-500" />;
      case 'submitted':
        return <ClockIcon className="h-5 w-5 text-blue-500" />;
      case 'under_review':
        return <ExclamationTriangleIcon className="h-5 w-5 text-yellow-500" />;
      case 'approved':
        return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
      case 'in_progress':
        return <ClockIcon className="h-5 w-5 text-orange-500" />;
      case 'completed':
        return <CheckCircleIcon className="h-5 w-5 text-green-600" />;
      case 'rejected':
        return <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />;
      default:
        return <DocumentTextIcon className="h-5 w-5 text-gray-500" />;
    }
  };

  const getStatusColor = (status: string): string => {
    const colors: { [key: string]: string } = {
      'draft': 'bg-gray-100 text-gray-800',
      'submitted': 'bg-blue-100 text-blue-800',
      'under_review': 'bg-yellow-100 text-yellow-800',
      'approved': 'bg-green-100 text-green-800',
      'in_progress': 'bg-orange-100 text-orange-800',
      'completed': 'bg-green-100 text-green-800',
      'rejected': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const getPriorityColor = (priority: string): string => {
    const colors: { [key: string]: string } = {
      'low': 'bg-blue-100 text-blue-800',
      'medium': 'bg-yellow-100 text-yellow-800',
      'high': 'bg-orange-100 text-orange-800',
      'critical': 'bg-red-100 text-red-800'
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
  };

  const filteredClaims = claims.filter(claim =>
    claim.claim_number.toLowerCase().includes(searchQuery.toLowerCase()) ||
    claim.vin.toLowerCase().includes(searchQuery.toLowerCase()) ||
    claim.customer_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    claim.issue_description.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center mb-2">
            <DocumentTextIcon className="h-8 w-8 text-purple-600 mr-3" />
            <h1 className="text-3xl font-bold text-gray-900">Quản Lý Yêu Cầu Bảo Hành</h1>
          </div>
          <p className="text-gray-600">Quản lý các yêu cầu bảo hành và vấn đề của khách hàng</p>
        </div>

        {/* Alert Messages */}
        {successMessage && (
          <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center">
            <CheckCircleIcon className="h-5 w-5 text-green-600 mr-2" />
            <span className="text-green-800">{successMessage}</span>
            <button
              onClick={() => setSuccessMessage('')}
              className="ml-auto text-green-600 hover:text-green-800"
            >
              ×
            </button>
          </div>
        )}

        {errorMessage && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center">
            <ExclamationTriangleIcon className="h-5 w-5 text-red-600 mr-2" />
            <span className="text-red-800">{errorMessage}</span>
            <button
              onClick={() => setErrorMessage('')}
              className="ml-auto text-red-600 hover:text-red-800"
            >
              ×
            </button>
          </div>
        )}

        {/* Tab Navigation */}
        <div className="mb-6">
          <div className="border-b border-gray-200">
            <nav className="-mb-px flex space-x-8">
              <button
                onClick={() => setActiveTab('list')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'list'
                    ? 'border-purple-500 text-purple-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Danh Sách Yêu Cầu Bảo Hành
              </button>
              <button
                onClick={() => setActiveTab('create')}
                className={`py-2 px-1 border-b-2 font-medium text-sm flex items-center ${
                  activeTab === 'create'
                    ? 'border-purple-500 text-purple-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <PlusIcon className="h-4 w-4 mr-1" />
                Tạo Yêu Cầu Mới
              </button>
            </nav>
          </div>
        </div>

        {/* Claims List Tab */}
        {activeTab === 'list' && (
          <div className="bg-white rounded-lg shadow-sm border">
            {/* Filters */}
            <div className="p-4 border-b border-gray-200">
              <div className="flex flex-col sm:flex-row gap-4">
                <div className="flex-1">
                  <div className="relative">
                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input
                      type="text"
                      placeholder="Tìm theo số yêu cầu, VIN, tên khách hàng hoặc vấn đề..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                  </div>
                </div>
                <div>
                  <select
                    value={statusFilter}
                    onChange={(e) => setStatusFilter(e.target.value)}
                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  >
                    <option value="all">Tất Cả Trạng Thái</option>
                    <option value="draft">Nháp</option>
                    <option value="submitted">Đã Gửi</option>
                    <option value="under_review">Đang Xem Xét</option>
                    <option value="approved">Đã Duyệt</option>
                    <option value="in_progress">Đang Xử Lý</option>
                    <option value="completed">Hoàn Thành</option>
                    <option value="rejected">Từ Chối</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Claims List */}
            <div className="p-4">
              {loading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                  <p className="mt-2 text-gray-600">Đang tải yêu cầu...</p>
                </div>
              ) : filteredClaims.length === 0 ? (
                <div className="text-center py-8">
                  <DocumentTextIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-600">Không tìm thấy yêu cầu bảo hành nào</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {filteredClaims.map((claim) => (
                    <div key={claim.id} className="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <div className="flex items-center gap-3 mb-2">
                            <h3 className="font-semibold text-gray-900">{claim.claim_number}</h3>
                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(claim.status)}`}>
                              {claim.status.replace('_', ' ').toUpperCase()}
                            </span>
                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getPriorityColor(claim.priority)}`}>
                              {claim.priority.toUpperCase()}
                            </span>
                          </div>
                          
                          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                            <div>
                              <p className="text-sm text-gray-600">
                                <strong>VIN:</strong> {claim.vin}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>Mẫu Xe:</strong> {claim.model_name}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>Khách Hàng:</strong> {claim.customer_name}
                              </p>
                            </div>
                            <div>
                              <p className="text-sm text-gray-600">
                                <strong>Ngày Tạo:</strong> {new Date(claim.created_at).toLocaleDateString()}
                              </p>
                              {claim.failure_date && (
                                <p className="text-sm text-gray-600">
                                  <strong>Ngày Hỏng:</strong> {new Date(claim.failure_date).toLocaleDateString()}
                                </p>
                              )}
                            </div>
                          </div>
                          
                          <p className="text-sm text-gray-800 mb-3">
                            <strong>Vấn Đề:</strong> {claim.issue_description}
                          </p>
                        </div>
                        
                        <div className="flex items-center gap-2 ml-4">
                          {getStatusIcon(claim.status)}
                          <button className="p-2 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg">
                            <EyeIcon className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Create New Claim Tab */}
        {activeTab === 'create' && (
          <div className="bg-white rounded-lg shadow-sm border">
            <form onSubmit={handleCreateClaim} className="p-6 space-y-6">
              <div>
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Tạo Yêu Cầu Bảo Hành Mới</h2>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Số VIN Xe *
                    </label>
                    <select
                      value={newClaim.vehicle_vin}
                      onChange={(e) => setNewClaim({ ...newClaim, vehicle_vin: e.target.value })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    >
                      <option value="">Chọn xe</option>
                      {vehicles.map((vehicle) => (
                        <option key={vehicle.id} value={vehicle.vin}>
                          {vehicle.vin} - {vehicle.model_name} ({vehicle.customer_name})
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Mức Độ Ưu Tiên *
                    </label>
                    <select
                      value={newClaim.priority}
                      onChange={(e) => setNewClaim({ ...newClaim, priority: e.target.value as any })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    >
                      <option value="low">Thấp</option>
                      <option value="medium">Trung Bình</option>
                      <option value="high">Cao</option>
                      <option value="critical">Khẩn Cấp</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Ngày Hỏng *
                    </label>
                    <input
                      type="date"
                      value={newClaim.failure_date}
                      onChange={(e) => setNewClaim({ ...newClaim, failure_date: e.target.value })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Số Km Khi Hỏng
                    </label>
                    <input
                      type="number"
                      value={newClaim.failure_mileage}
                      onChange={(e) => setNewClaim({ ...newClaim, failure_mileage: e.target.value })}
                      placeholder="12000"
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                  </div>
                </div>

                <div className="mt-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Mô Tả Vấn Đề *
                  </label>
                  <textarea
                    value={newClaim.issue_description}
                    onChange={(e) => setNewClaim({ ...newClaim, issue_description: e.target.value })}
                    placeholder="Mô tả chi tiết vấn đề..."
                    rows={4}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Triệu Chứng & Chi Tiết Bổ Sung
                  </label>
                  <textarea
                    value={newClaim.symptoms}
                    onChange={(e) => setNewClaim({ ...newClaim, symptoms: e.target.value })}
                    placeholder="Liệt kê các triệu chứng hoặc chi tiết bổ sung..."
                    rows={3}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div className="flex justify-end pt-4 border-t">
                <div className="flex gap-3">
                  <button
                    type="button"
                    onClick={() => setActiveTab('list')}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                  >
                    Hủy
                  </button>
                  <button
                    type="submit"
                    disabled={loading}
                    className="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center"
                  >
                    {loading ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        Đang tạo...
                      </>
                    ) : (
                      <>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Tạo Yêu Cầu
                      </>
                    )}
                  </button>
                </div>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  );
}