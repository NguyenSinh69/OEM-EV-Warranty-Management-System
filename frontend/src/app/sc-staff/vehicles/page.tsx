'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { api } from '@/lib/api';
import { 
  MagnifyingGlassIcon, 
  FunnelIcon,
  TruckIcon,
  CalendarIcon,
  UserIcon,
  ShieldCheckIcon,
  ClockIcon
} from '@heroicons/react/24/outline';
import LoadingSpinner from '@/components/shared/LoadingSpinner';
import EmptyState from '@/components/shared/EmptyState';
import StatusBadge from '@/components/shared/StatusBadge';

interface Vehicle {
  id: number;
  vin: string;
  license_plate: string;
  model_name: string;
  model_full_name: string;
  year: number;
  color: string;
  customer_name: string;
  customer_phone: string;
  purchase_date: string;
  warranty_start_date: string;
  warranty_end_date: string;
  current_mileage: number;
  status: string;
  created_at: string;
}

export default function VehicleListPage() {
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [filteredVehicles, setFilteredVehicles] = useState<Vehicle[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchType, setSearchType] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');

  useEffect(() => {
    loadVehicles();
  }, []);

  useEffect(() => {
    filterVehicles();
  }, [searchQuery, searchType, statusFilter, vehicles]);

  const loadVehicles = async () => {
    try {
      setLoading(true);
      const response = await api.scStaff.searchVehicles('', 'all');
      if (response.success) {
        setVehicles(response.data || []);
      }
    } catch (error) {
      console.error('Error loading vehicles:', error);
    } finally {
      setLoading(false);
    }
  };

  const filterVehicles = () => {
    let filtered = [...vehicles];

    // Filter by search query
    if (searchQuery) {
      filtered = filtered.filter(vehicle => {
        const query = searchQuery.toLowerCase();
        switch (searchType) {
          case 'vin':
            return vehicle.vin.toLowerCase().includes(query);
          case 'license_plate':
            return vehicle.license_plate?.toLowerCase().includes(query);
          case 'customer':
            return vehicle.customer_name.toLowerCase().includes(query);
          default:
            return (
              vehicle.vin.toLowerCase().includes(query) ||
              vehicle.license_plate?.toLowerCase().includes(query) ||
              vehicle.customer_name.toLowerCase().includes(query) ||
              vehicle.model_name.toLowerCase().includes(query)
            );
        }
      });
    }

    // Filter by status
    if (statusFilter !== 'all') {
      filtered = filtered.filter(vehicle => vehicle.status === statusFilter);
    }

    setFilteredVehicles(filtered);
  };

  const getWarrantyStatus = (endDate: string) => {
    const today = new Date();
    const warranty = new Date(endDate);
    const daysLeft = Math.ceil((warranty.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

    if (daysLeft < 0) return { status: 'expired', label: 'Hết hạn', color: 'red' };
    if (daysLeft <= 30) return { status: 'expiring', label: `Còn ${daysLeft} ngày`, color: 'orange' };
    if (daysLeft <= 90) return { status: 'warning', label: `Còn ${daysLeft} ngày`, color: 'yellow' };
    return { status: 'active', label: `Còn ${daysLeft} ngày`, color: 'green' };
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('vi-VN');
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Danh Sách Xe Đã Đăng Ký</h1>
          <p className="text-gray-600 mt-1">
            Quản lý và theo dõi tất cả xe điện đã đăng ký
          </p>
        </div>
        <Link
          href="/sc-staff/vehicle-registration"
          className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
          <TruckIcon className="w-5 h-5 mr-2" />
          Đăng Ký Xe Mới
        </Link>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Tổng số xe</p>
              <p className="text-2xl font-bold text-gray-900">{vehicles.length}</p>
            </div>
            <TruckIcon className="w-10 h-10 text-indigo-600" />
          </div>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Còn bảo hành</p>
              <p className="text-2xl font-bold text-green-600">
                {vehicles.filter(v => v.status === 'under_warranty').length}
              </p>
            </div>
            <ShieldCheckIcon className="w-10 h-10 text-green-600" />
          </div>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Hết bảo hành</p>
              <p className="text-2xl font-bold text-red-600">
                {vehicles.filter(v => v.status === 'warranty_expired').length}
              </p>
            </div>
            <ClockIcon className="w-10 h-10 text-red-600" />
          </div>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Đăng ký hôm nay</p>
              <p className="text-2xl font-bold text-blue-600">
                {vehicles.filter(v => {
                  const today = new Date().toDateString();
                  const created = new Date(v.created_at).toDateString();
                  return today === created;
                }).length}
              </p>
            </div>
            <CalendarIcon className="w-10 h-10 text-blue-600" />
          </div>
        </div>
      </div>

      {/* Search and Filters */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Search */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Tìm kiếm
            </label>
            <div className="relative">
              <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                type="text"
                placeholder="Tìm theo VIN, biển số, tên khách hàng..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Search Type */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Loại tìm kiếm
            </label>
            <select
              value={searchType}
              onChange={(e) => setSearchType(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            >
              <option value="all">Tất cả</option>
              <option value="vin">VIN</option>
              <option value="license_plate">Biển số</option>
              <option value="customer">Khách hàng</option>
            </select>
          </div>
        </div>

        {/* Status Filter */}
        <div className="mt-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            <FunnelIcon className="inline w-4 h-4 mr-1" />
            Lọc theo trạng thái
          </label>
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setStatusFilter('all')}
              className={`px-4 py-2 rounded-lg transition-colors ${
                statusFilter === 'all'
                  ? 'bg-indigo-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              Tất cả ({vehicles.length})
            </button>
            <button
              onClick={() => setStatusFilter('under_warranty')}
              className={`px-4 py-2 rounded-lg transition-colors ${
                statusFilter === 'under_warranty'
                  ? 'bg-green-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              Còn bảo hành ({vehicles.filter(v => v.status === 'under_warranty').length})
            </button>
            <button
              onClick={() => setStatusFilter('warranty_expired')}
              className={`px-4 py-2 rounded-lg transition-colors ${
                statusFilter === 'warranty_expired'
                  ? 'bg-red-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              Hết bảo hành ({vehicles.filter(v => v.status === 'warranty_expired').length})
            </button>
          </div>
        </div>
      </div>

      {/* Vehicle List */}
      {filteredVehicles.length === 0 ? (
        <EmptyState
          title="Không tìm thấy xe nào"
          description="Thử thay đổi bộ lọc hoặc tìm kiếm với từ khóa khác"
          actionLabel="Đăng ký xe mới"
          actionHref="/sc-staff/vehicle-registration"
        />
      ) : (
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Thông tin xe
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Khách hàng
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Bảo hành
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Km đã đi
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Trạng thái
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Ngày đăng ký
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredVehicles.map((vehicle) => {
                  const warrantyStatus = getWarrantyStatus(vehicle.warranty_end_date);
                  return (
                    <tr key={vehicle.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                            <TruckIcon className="h-6 w-6 text-indigo-600" />
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-medium text-gray-900">
                              {vehicle.model_full_name}
                            </div>
                            <div className="text-sm text-gray-500">
                              VIN: {vehicle.vin}
                            </div>
                            <div className="text-sm text-gray-500">
                              Biển: {vehicle.license_plate || 'Chưa có'}
                            </div>
                            <div className="text-sm text-gray-500">
                              Năm {vehicle.year} • {vehicle.color}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <UserIcon className="w-5 h-5 text-gray-400 mr-2" />
                          <div>
                            <div className="text-sm font-medium text-gray-900">
                              {vehicle.customer_name}
                            </div>
                            <div className="text-sm text-gray-500">
                              {vehicle.customer_phone}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          {formatDate(vehicle.warranty_start_date)}
                        </div>
                        <div className="text-sm text-gray-500">
                          đến {formatDate(vehicle.warranty_end_date)}
                        </div>
                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mt-1 ${
                          warrantyStatus.color === 'green' ? 'bg-green-100 text-green-800' :
                          warrantyStatus.color === 'yellow' ? 'bg-yellow-100 text-yellow-800' :
                          warrantyStatus.color === 'orange' ? 'bg-orange-100 text-orange-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {warrantyStatus.label}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          {vehicle.current_mileage?.toLocaleString() || 0} km
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <StatusBadge status={vehicle.status} />
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {formatDate(vehicle.created_at)}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Pagination info */}
          <div className="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div className="text-sm text-gray-700">
              Hiển thị <span className="font-medium">{filteredVehicles.length}</span> xe
              {searchQuery && ` (lọc từ ${vehicles.length} xe)`}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
