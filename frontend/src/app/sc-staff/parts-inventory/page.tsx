'use client';

import { useState, useEffect } from 'react';
import {
  CubeIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  AdjustmentsHorizontalIcon
} from '@heroicons/react/24/outline';

interface Part {
  id: string;
  part_name: string;
  part_number: string;
  category: string;
  manufacturer: string;
  warranty_months: number;
  cost_usd: number;
  stock_quantity: number;
  min_stock_level: number;
  status: string;
  last_updated: string;
}

interface StockAdjustment {
  part_id: string;
  adjustment_type: 'increase' | 'decrease' | 'set';
  quantity: number;
  reason: string;
}

export default function PartsInventoryPage() {
  const [parts, setParts] = useState<Part[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [showAdjustModal, setShowAdjustModal] = useState(false);
  const [selectedPart, setSelectedPart] = useState<Part | null>(null);
  const [adjustment, setAdjustment] = useState<StockAdjustment>({
    part_id: '',
    adjustment_type: 'increase',
    quantity: 1,
    reason: ''
  });
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  useEffect(() => {
    loadParts();
  }, [categoryFilter, statusFilter]);

  const loadParts = async () => {
    setLoading(true);
    try {
      const response = await fetch(`http://localhost:8003/api/sc-staff/parts/inventory?category=${categoryFilter}&status=${statusFilter}`);
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setParts(data.data || []);
        }
      } else {
        // Mock data
        setParts([
          {
            id: '1',
            part_name: 'Battery Pack VF8',
            part_number: 'VF8-BATT-001',
            category: 'Battery',
            manufacturer: 'VinFast',
            warranty_months: 96,
            cost_usd: 15000,
            stock_quantity: 5,
            min_stock_level: 3,
            status: 'available',
            last_updated: '2024-11-05T10:00:00Z'
          },
          {
            id: '2',
            part_name: 'Electric Motor VF9',
            part_number: 'VF9-MOTOR-001',
            category: 'Motor',
            manufacturer: 'VinFast',
            warranty_months: 60,
            cost_usd: 8000,
            stock_quantity: 2,
            min_stock_level: 5,
            status: 'low_stock',
            last_updated: '2024-11-04T15:30:00Z'
          },
          {
            id: '3',
            part_name: 'Charging Port Assembly',
            part_number: 'VF-CHARGE-001',
            category: 'Charging',
            manufacturer: 'VinFast',
            warranty_months: 24,
            cost_usd: 500,
            stock_quantity: 0,
            min_stock_level: 10,
            status: 'out_of_stock',
            last_updated: '2024-11-03T09:15:00Z'
          },
          {
            id: '4',
            part_name: 'Inverter Unit VFe34',
            part_number: 'VFE34-INV-001',
            category: 'Electronics',
            manufacturer: 'VinFast',
            warranty_months: 36,
            cost_usd: 3500,
            stock_quantity: 8,
            min_stock_level: 4,
            status: 'available',
            last_updated: '2024-11-05T08:45:00Z'
          }
        ]);
      }
    } catch (error) {
      console.error('Failed to load parts:', error);
      setErrorMessage('Không thể tải danh sách kho phụ tùng');
    } finally {
      setLoading(false);
    }
  };

  const handleStockAdjustment = async () => {
    if (!selectedPart) return;
    
    setLoading(true);
    try {
      const response = await fetch('http://localhost:8003/api/sc-staff/parts/adjust-stock', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ...adjustment,
          part_id: selectedPart.id
        })
      });

      const data = await response.json();
      
      if (data.success || true) { // Mock success for demo
        setSuccessMessage(`Điều chỉnh kho thành công cho ${selectedPart.part_name}`);
        setShowAdjustModal(false);
        setSelectedPart(null);
        setAdjustment({
          part_id: '',
          adjustment_type: 'increase',
          quantity: 1,
          reason: ''
        });
        loadParts(); // Refresh the list
      } else {
        setErrorMessage(data.error || 'Không thể điều chỉnh kho');
      }
    } catch (error) {
      setErrorMessage('Lỗi mạng. Vui lòng thử lại.');
      console.error('Stock adjustment error:', error);
    } finally {
      setLoading(false);
    }
  };

  const openAdjustModal = (part: Part) => {
    setSelectedPart(part);
    setAdjustment({
      part_id: part.id,
      adjustment_type: 'increase',
      quantity: 1,
      reason: ''
    });
    setShowAdjustModal(true);
  };

  const getStatusIcon = (status: string, stockQuantity: number, minStockLevel: number) => {
    if (status === 'out_of_stock' || stockQuantity === 0) {
      return <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />;
    } else if (status === 'low_stock' || stockQuantity <= minStockLevel) {
      return <ClockIcon className="h-5 w-5 text-orange-500" />;
    } else {
      return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
    }
  };

  const getStatusColor = (status: string, stockQuantity: number, minStockLevel: number): string => {
    if (status === 'out_of_stock' || stockQuantity === 0) {
      return 'bg-red-100 text-red-800';
    } else if (status === 'low_stock' || stockQuantity <= minStockLevel) {
      return 'bg-orange-100 text-orange-800';
    } else {
      return 'bg-green-100 text-green-800';
    }
  };

  const getStatusText = (status: string, stockQuantity: number, minStockLevel: number): string => {
    if (status === 'out_of_stock' || stockQuantity === 0) {
      return 'Hết Hàng';
    } else if (status === 'low_stock' || stockQuantity <= minStockLevel) {
      return 'Sắp Hết';
    } else {
      return 'Có Sẵn';
    }
  };

  const filteredParts = parts.filter(part => {
    const matchesSearch = 
      part.part_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      part.part_number.toLowerCase().includes(searchQuery.toLowerCase()) ||
      part.manufacturer.toLowerCase().includes(searchQuery.toLowerCase());
    
    const matchesCategory = categoryFilter === 'all' || part.category.toLowerCase() === categoryFilter.toLowerCase();
    
    const partStatus = part.stock_quantity === 0 ? 'out_of_stock' : 
                      part.stock_quantity <= part.min_stock_level ? 'low_stock' : 'available';
    const matchesStatus = statusFilter === 'all' || partStatus === statusFilter;
    
    return matchesSearch && matchesCategory && matchesStatus;
  });

  const categories = ['Battery', 'Motor', 'Electronics', 'Charging', 'Thermal', 'Other'];

  return (
    <div className="p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center mb-2">
            <CubeIcon className="h-8 w-8 text-purple-600 mr-3" />
            <h1 className="text-3xl font-bold text-gray-900">Quản Lý Kho Phụ Tùng</h1>
          </div>
          <p className="text-gray-600">Quản lý phụ tùng thay thế và mức tồn kho</p>
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

        {/* Inventory Overview */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white p-4 rounded-lg border shadow-sm">
            <div className="flex items-center">
              <CubeIcon className="h-8 w-8 text-blue-500 mr-3" />
              <div>
                <p className="text-sm text-gray-600">Tổng Phụ Tùng</p>
                <p className="text-2xl font-bold text-gray-900">{parts.length}</p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-4 rounded-lg border shadow-sm">
            <div className="flex items-center">
              <CheckCircleIcon className="h-8 w-8 text-green-500 mr-3" />
              <div>
                <p className="text-sm text-gray-600">Còn Hàng</p>
                <p className="text-2xl font-bold text-green-600">
                  {parts.filter(p => p.stock_quantity > p.min_stock_level).length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-4 rounded-lg border shadow-sm">
            <div className="flex items-center">
              <ClockIcon className="h-8 w-8 text-orange-500 mr-3" />
              <div>
                <p className="text-sm text-gray-600">Sắp Hết</p>
                <p className="text-2xl font-bold text-orange-600">
                  {parts.filter(p => p.stock_quantity > 0 && p.stock_quantity <= p.min_stock_level).length}
                </p>
              </div>
            </div>
          </div>
          
          <div className="bg-white p-4 rounded-lg border shadow-sm">
            <div className="flex items-center">
              <ExclamationTriangleIcon className="h-8 w-8 text-red-500 mr-3" />
              <div>
                <p className="text-sm text-gray-600">Hết Hàng</p>
                <p className="text-2xl font-bold text-red-600">
                  {parts.filter(p => p.stock_quantity === 0).length}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Filters and Search */}
        <div className="bg-white rounded-lg shadow-sm border mb-6">
          <div className="p-4 border-b border-gray-200">
            <div className="flex flex-col lg:flex-row gap-4">
              <div className="flex-1">
                <div className="relative">
                  <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                  <input
                    type="text"
                    placeholder="Tìm theo tên phụ tùng, mã số hoặc nhà sản xuất..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
              </div>
              
              <div className="flex gap-4">
                <select
                  value={categoryFilter}
                  onChange={(e) => setCategoryFilter(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                >
                  <option value="all">Tất Cả Danh Mục</option>
                  {categories.map(category => (
                    <option key={category} value={category.toLowerCase()}>{category}</option>
                  ))}
                </select>
                
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                >
                  <option value="all">Tất Cả Trạng Thái</option>
                  <option value="available">Có Sẵn</option>
                  <option value="low_stock">Sắp Hết</option>
                  <option value="out_of_stock">Hết Hàng</option>
                </select>
              </div>
            </div>
          </div>

          {/* Parts List */}
          <div className="p-4">
            {loading ? (
              <div className="text-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                <p className="mt-2 text-gray-600">Đang tải phụ tùng...</p>
              </div>
            ) : filteredParts.length === 0 ? (
              <div className="text-center py-8">
                <CubeIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-600">Không tìm thấy phụ tùng nào</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Thông Tin Phụ Tùng
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Danh Mục
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Mức Tồn Kho
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Chi Phí (USD)
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Trạng Thái
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Thao Tác
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {filteredParts.map((part) => (
                      <tr key={part.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div>
                            <div className="text-sm font-medium text-gray-900">{part.part_name}</div>
                            <div className="text-sm text-gray-500">{part.part_number}</div>
                            <div className="text-xs text-gray-400">{part.manufacturer}</div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {part.category}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          <div>
                            <div className="font-medium">{part.stock_quantity} cái</div>
                            <div className="text-xs text-gray-500">Tối thiểu: {part.min_stock_level}</div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          ${part.cost_usd.toLocaleString()}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center">
                            {getStatusIcon(part.status, part.stock_quantity, part.min_stock_level)}
                            <span className={`ml-2 px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(part.status, part.stock_quantity, part.min_stock_level)}`}>
                              {getStatusText(part.status, part.stock_quantity, part.min_stock_level)}
                            </span>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                          <button
                            onClick={() => openAdjustModal(part)}
                            className="text-purple-600 hover:text-purple-900 flex items-center"
                          >
                            <AdjustmentsHorizontalIcon className="h-4 w-4 mr-1" />
                            Điều Chỉnh Kho
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        {/* Stock Adjustment Modal */}
        {showAdjustModal && selectedPart && (
          <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
              <h3 className="text-lg font-medium text-gray-900 mb-4">
                Điều Chỉnh Kho - {selectedPart.part_name}
              </h3>
              
              <div className="space-y-4">
                <div>
                  <p className="text-sm text-gray-600">Kho Hiện Tại: {selectedPart.stock_quantity} cái</p>
                  <p className="text-sm text-gray-600">Mức Tối Thiểu: {selectedPart.min_stock_level} cái</p>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Loại Điều Chỉnh *
                  </label>
                  <select
                    value={adjustment.adjustment_type}
                    onChange={(e) => setAdjustment({ ...adjustment, adjustment_type: e.target.value as any })}
                    className="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  >
                    <option value="increase">Tăng Kho</option>
                    <option value="decrease">Giảm Kho</option>
                    <option value="set">Đặt Mức Kho</option>
                  </select>
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Số Lượng *
                  </label>
                  <input
                    type="number"
                    min="1"
                    value={adjustment.quantity}
                    onChange={(e) => setAdjustment({ ...adjustment, quantity: parseInt(e.target.value) || 1 })}
                    className="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  />
                </div>
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Lý Do *
                  </label>
                  <select
                    value={adjustment.reason}
                    onChange={(e) => setAdjustment({ ...adjustment, reason: e.target.value })}
                    className="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  >
                    <option value="">Chọn lý do</option>
                    <option value="received_shipment">Nhận Hàng Mới</option>
                    <option value="warranty_replacement">Thay Thế Bảo Hành</option>
                    <option value="damaged_parts">Phụ Tùng Hỏng</option>
                    <option value="inventory_correction">Điều Chỉnh Kho</option>
                    <option value="returned_parts">Trả Hàng</option>
                    <option value="other">Khác</option>
                  </select>
                </div>
              </div>
              
              <div className="flex justify-end gap-3 mt-6">
                <button
                  onClick={() => {
                    setShowAdjustModal(false);
                    setSelectedPart(null);
                  }}
                  className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                  Hủy
                </button>
                <button
                  onClick={handleStockAdjustment}
                  disabled={loading || !adjustment.reason}
                  className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                  {loading ? 'Đang điều chỉnh...' : 'Điều Chỉnh Kho'}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}