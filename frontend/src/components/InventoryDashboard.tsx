import React, { useState, useEffect } from 'react';
import {
  CubeIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  AdjustmentsHorizontalIcon,
  ArrowPathIcon,
  PlusIcon,
  BellAlertIcon,
  ChartBarIcon
} from '@heroicons/react/24/outline';

interface InventoryItem {
  id: number;
  part_number: string;
  name: string;
  description?: string;
  category: string;
  current_stock: number;
  min_stock_level: number;
  max_stock_level: number;
  unit_price: number;
  currency: string;
  supplier: string;
  location: string;
  last_updated: string;
  status: 'available' | 'low_stock' | 'out_of_stock' | 'discontinued';
}

interface StockAlert {
  id: number;
  inventory_id: number;
  alert_type: 'low_stock' | 'out_of_stock' | 'overstocked';
  message: string;
  created_at: string;
  part_number: string;
  part_name: string;
  current_stock: number;
  threshold: number;
}

interface InventoryStats {
  total_items: number;
  low_stock_items: number;
  out_of_stock_items: number;
  total_value: number;
  currency: string;
}

interface InventoryDashboardProps {
  serviceCenterId?: number;
}

export default function InventoryDashboard({ serviceCenterId }: InventoryDashboardProps) {
  const [items, setItems] = useState<InventoryItem[]>([]);
  const [alerts, setAlerts] = useState<StockAlert[]>([]);
  const [stats, setStats] = useState<InventoryStats | null>(null);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [selectedStatus, setSelectedStatus] = useState('');
  const [showAlertsPanel, setShowAlertsPanel] = useState(false);
  const [showStockUpdateModal, setShowStockUpdateModal] = useState(false);
  const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(null);

  useEffect(() => {
    fetchInventoryData();
    fetchAlerts();
  }, [serviceCenterId, selectedCategory, selectedStatus]);

  const fetchInventoryData = async () => {
    try {
      setLoading(true);
      
      const params = new URLSearchParams({
        ...(serviceCenterId && { service_center_id: serviceCenterId.toString() }),
        ...(selectedCategory && { category: selectedCategory }),
        ...(selectedStatus && { status: selectedStatus }),
        ...(searchTerm && { search: searchTerm })
      });

      const response = await fetch(`http://localhost:8005/api/inventory?${params}`);
      const data = await response.json();

      if (data.success) {
        setItems(data.data.items);
        setStats(data.data.stats);
      }
    } catch (error) {
      console.error('Failed to fetch inventory:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchAlerts = async () => {
    try {
      const params = new URLSearchParams({
        ...(serviceCenterId && { service_center_id: serviceCenterId.toString() })
      });

      const response = await fetch(`http://localhost:8005/api/inventory/alerts?${params}`);
      const data = await response.json();

      if (data.success) {
        setAlerts(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch alerts:', error);
    }
  };

  const handleStockUpdate = async (itemId: number, newStock: number, reason: string) => {
    try {
      const response = await fetch(`http://localhost:8005/api/inventory/${itemId}/stock`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          stock_change: newStock,
          reason,
          updated_by: 'system' // Replace with actual user
        })
      });

      const data = await response.json();

      if (data.success) {
        fetchInventoryData();
        fetchAlerts();
        setShowStockUpdateModal(false);
        setSelectedItem(null);
      }
    } catch (error) {
      console.error('Failed to update stock:', error);
    }
  };

  const handleSearch = () => {
    fetchInventoryData();
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'available':
        return 'bg-green-100 text-green-800';
      case 'low_stock':
        return 'bg-yellow-100 text-yellow-800';
      case 'out_of_stock':
        return 'bg-red-100 text-red-800';
      case 'discontinued':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'available':
        return 'Có sẵn';
      case 'low_stock':
        return 'Sắp hết';
      case 'out_of_stock':
        return 'Hết hàng';
      case 'discontinued':
        return 'Ngừng kinh doanh';
      default:
        return status;
    }
  };

  const getAlertColor = (alertType: string) => {
    switch (alertType) {
      case 'low_stock':
        return 'bg-yellow-50 border-yellow-200 text-yellow-800';
      case 'out_of_stock':
        return 'bg-red-50 border-red-200 text-red-800';
      case 'overstocked':
        return 'bg-blue-50 border-blue-200 text-blue-800';
      default:
        return 'bg-gray-50 border-gray-200 text-gray-800';
    }
  };

  const categories = [...new Set(items.map(item => item.category))];

  return (
    <div className="space-y-6">
      {/* Header with stats */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold flex items-center">
            <CubeIcon className="h-8 w-8 mr-3 text-blue-600" />
            Quản lý kho
          </h1>
          
          <div className="flex space-x-3">
            <button
              onClick={() => setShowAlertsPanel(!showAlertsPanel)}
              className={`px-4 py-2 rounded-lg flex items-center space-x-2 ${
                alerts.length > 0
                  ? 'bg-red-100 text-red-700 hover:bg-red-200'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              <BellAlertIcon className="h-5 w-5" />
              <span>Cảnh báo ({alerts.length})</span>
            </button>
            
            <button
              onClick={() => fetchInventoryData()}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2"
            >
              <ArrowPathIcon className="h-5 w-5" />
              <span>Làm mới</span>
            </button>
          </div>
        </div>

        {/* Stats */}
        {stats && (
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="bg-blue-50 rounded-lg p-4">
              <div className="flex items-center">
                <ChartBarIcon className="h-8 w-8 text-blue-600" />
                <div className="ml-3">
                  <p className="text-sm font-medium text-blue-600">Tổng sản phẩm</p>
                  <p className="text-2xl font-bold text-blue-900">{stats.total_items}</p>
                </div>
              </div>
            </div>
            
            <div className="bg-yellow-50 rounded-lg p-4">
              <div className="flex items-center">
                <ExclamationTriangleIcon className="h-8 w-8 text-yellow-600" />
                <div className="ml-3">
                  <p className="text-sm font-medium text-yellow-600">Sắp hết hàng</p>
                  <p className="text-2xl font-bold text-yellow-900">{stats.low_stock_items}</p>
                </div>
              </div>
            </div>
            
            <div className="bg-red-50 rounded-lg p-4">
              <div className="flex items-center">
                <CubeIcon className="h-8 w-8 text-red-600" />
                <div className="ml-3">
                  <p className="text-sm font-medium text-red-600">Hết hàng</p>
                  <p className="text-2xl font-bold text-red-900">{stats.out_of_stock_items}</p>
                </div>
              </div>
            </div>
            
            <div className="bg-green-50 rounded-lg p-4">
              <div className="flex items-center">
                <div className="text-green-600 text-2xl font-bold">$</div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-green-600">Tổng giá trị</p>
                  <p className="text-2xl font-bold text-green-900">
                    {stats.total_value.toLocaleString()} {stats.currency}
                  </p>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Alerts Panel */}
      {showAlertsPanel && (
        <div className="bg-white rounded-lg shadow">
          <div className="p-4 border-b">
            <h3 className="text-lg font-medium">Cảnh báo kho</h3>
          </div>
          <div className="p-4 max-h-64 overflow-y-auto">
            {alerts.length === 0 ? (
              <p className="text-gray-500 text-center py-4">Không có cảnh báo nào</p>
            ) : (
              <div className="space-y-3">
                {alerts.map(alert => (
                  <div
                    key={alert.id}
                    className={`p-3 rounded-lg border ${getAlertColor(alert.alert_type)}`}
                  >
                    <div className="flex items-start justify-between">
                      <div>
                        <p className="font-medium">{alert.part_name}</p>
                        <p className="text-sm">{alert.message}</p>
                        <p className="text-xs mt-1">
                          Mã phụ tủng: {alert.part_number} | 
                          Tồn kho: {alert.current_stock} | 
                          Ngưỡng: {alert.threshold}
                        </p>
                      </div>
                      <span className="text-xs">
                        {new Date(alert.created_at).toLocaleDateString('vi-VN')}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Filters and Search */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Search */}
          <div className="flex">
            <input
              type="text"
              placeholder="Tìm kiếm theo tên hoặc mã phụ tùng..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="flex-1 p-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500"
              onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
            />
            <button
              onClick={handleSearch}
              className="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700"
            >
              <MagnifyingGlassIcon className="h-5 w-5" />
            </button>
          </div>

          {/* Category filter */}
          <select
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value)}
            className="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Tất cả danh mục</option>
            {categories.map(category => (
              <option key={category} value={category}>{category}</option>
            ))}
          </select>

          {/* Status filter */}
          <select
            value={selectedStatus}
            onChange={(e) => setSelectedStatus(e.target.value)}
            className="p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Tất cả trạng thái</option>
            <option value="available">Có sẵn</option>
            <option value="low_stock">Sắp hết</option>
            <option value="out_of_stock">Hết hàng</option>
            <option value="discontinued">Ngừng kinh doanh</option>
          </select>

          {/* Add new item button */}
          <button className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-2">
            <PlusIcon className="h-5 w-5" />
            <span>Thêm mới</span>
          </button>
        </div>
      </div>

      {/* Inventory Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Phụ tùng
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Danh mục
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tồn kho
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Giá
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Trạng thái
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Vị trí
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Thao tác
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-6 py-4 text-center">
                    <div className="flex justify-center items-center">
                      <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                      <span className="ml-2">Đang tải...</span>
                    </div>
                  </td>
                </tr>
              ) : items.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-4 text-center text-gray-500">
                    Không tìm thấy sản phẩm nào
                  </td>
                </tr>
              ) : (
                items.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{item.name}</div>
                        <div className="text-sm text-gray-500">Mã: {item.part_number}</div>
                        {item.description && (
                          <div className="text-xs text-gray-400 mt-1">{item.description}</div>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
                        {item.category}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">
                        {item.current_stock}
                        {item.current_stock <= item.min_stock_level && (
                          <ExclamationTriangleIcon className="h-4 w-4 text-yellow-500 ml-1 inline" />
                        )}
                      </div>
                      <div className="text-xs text-gray-500">
                        Min: {item.min_stock_level} | Max: {item.max_stock_level}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.unit_price.toLocaleString()} {item.currency}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 py-1 text-xs font-medium rounded ${getStatusColor(item.status)}`}>
                        {getStatusText(item.status)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.location}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <button
                        onClick={() => {
                          setSelectedItem(item);
                          setShowStockUpdateModal(true);
                        }}
                        className="text-blue-600 hover:text-blue-900 mr-3"
                      >
                        Cập nhật
                      </button>
                      <button className="text-green-600 hover:text-green-900">
                        Chi tiết
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Stock Update Modal */}
      {showStockUpdateModal && selectedItem && (
        <StockUpdateModal
          item={selectedItem}
          onClose={() => {
            setShowStockUpdateModal(false);
            setSelectedItem(null);
          }}
          onUpdate={handleStockUpdate}
        />
      )}
    </div>
  );
}

// Stock Update Modal Component
interface StockUpdateModalProps {
  item: InventoryItem;
  onClose: () => void;
  onUpdate: (itemId: number, newStock: number, reason: string) => void;
}

function StockUpdateModal({ item, onClose, onUpdate }: StockUpdateModalProps) {
  const [stockChange, setStockChange] = useState(0);
  const [reason, setReason] = useState('');
  const [operation, setOperation] = useState<'add' | 'subtract' | 'set'>('add');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    let newStock: number;
    switch (operation) {
      case 'add':
        newStock = item.current_stock + stockChange;
        break;
      case 'subtract':
        newStock = item.current_stock - stockChange;
        break;
      case 'set':
        newStock = stockChange;
        break;
    }

    onUpdate(item.id, newStock, reason);
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-md w-full m-4">
        <div className="p-6">
          <h3 className="text-lg font-medium mb-4">Cập nhật tồn kho</h3>
          
          <div className="mb-4">
            <p className="font-medium">{item.name}</p>
            <p className="text-sm text-gray-500">Mã: {item.part_number}</p>
            <p className="text-sm text-gray-500">Tồn kho hiện tại: {item.current_stock}</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Thao tác
              </label>
              <select
                value={operation}
                onChange={(e) => setOperation(e.target.value as 'add' | 'subtract' | 'set')}
                className="w-full p-2 border border-gray-300 rounded-lg"
              >
                <option value="add">Nhập thêm</option>
                <option value="subtract">Xuất kho</option>
                <option value="set">Đặt số lượng</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Số lượng
              </label>
              <input
                type="number"
                value={stockChange}
                onChange={(e) => setStockChange(parseInt(e.target.value) || 0)}
                className="w-full p-2 border border-gray-300 rounded-lg"
                min="0"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Lý do
              </label>
              <textarea
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                className="w-full p-2 border border-gray-300 rounded-lg"
                rows={3}
                required
                placeholder="Nhập lý do thay đổi tồn kho..."
              />
            </div>

            <div className="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Hủy
              </button>
              <button
                type="submit"
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                Cập nhật
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}