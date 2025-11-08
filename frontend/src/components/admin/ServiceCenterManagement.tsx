'use client';

import BaseLayout from '@/components/layout/BaseLayout';
import { PageContainer, Card, EmptyState } from '@/components/ui';
import { 
  ChartBarIcon,
  UserGroupIcon,
  BuildingOfficeIcon,
  PlusIcon,
  MapPinIcon
} from '@heroicons/react/24/outline';

const adminNavigation = [
  { name: 'Dashboard', href: '/admin', icon: ChartBarIcon },
  { name: 'Account & Role Management', href: '/admin/accounts', icon: UserGroupIcon },
  { name: 'Service Center Management', href: '/admin/service-centers', icon: BuildingOfficeIcon, current: true },
];

export default function ServiceCenterManagement() {
  return (
    <BaseLayout
      navigation={adminNavigation}
      userRole="admin"
      headerTitle="Service Center Management"
      headerSubtitle="Quản lý trung tâm dịch vụ"
      headerActions={
        <button className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
          <PlusIcon className="h-5 w-5 mr-2" />
          Thêm Service Center
        </button>
      }
    >
      <PageContainer>
        {/* SC Statistics */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card title="Tổng SC" className="text-center">
            <div className="text-2xl font-bold text-blue-600">15</div>
            <p className="text-sm text-gray-500">Đang hoạt động</p>
          </Card>
          
          <Card title="Miền Bắc" className="text-center">
            <div className="text-2xl font-bold text-green-600">6</div>
            <p className="text-sm text-gray-500">Service Centers</p>
          </Card>
          
          <Card title="Miền Trung" className="text-center">
            <div className="text-2xl font-bold text-yellow-600">3</div>
            <p className="text-sm text-gray-500">Service Centers</p>
          </Card>
          
          <Card title="Miền Nam" className="text-center">
            <div className="text-2xl font-bold text-red-600">6</div>
            <p className="text-sm text-gray-500">Service Centers</p>
          </Card>
        </div>

        {/* SC Management */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <div className="lg:col-span-2">
            <Card title="Danh sách Service Centers">
              <EmptyState
                title="Bảng quản lý Service Centers"
                description="Danh sách SC (tên, địa chỉ, người phụ trách)"
                action={
                  <div className="space-y-2 text-xs text-gray-400">
                    <p>• Thêm / ngừng hoạt động / cập nhật SC</p>
                    <p>• Theo dõi hiệu suất từng SC</p>
                    <p>• Bảng dữ liệu sẽ được thêm vào đây</p>
                  </div>
                }
              />
            </Card>
          </div>
          
          <div>
            <Card title="Thao tác nhanh">
              <div className="space-y-4">
                <button className="w-full p-3 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 flex items-center">
                  <MapPinIcon className="h-5 w-5 mr-2" />
                  Thêm SC mới
                </button>
                <button className="w-full p-3 border border-green-300 text-green-700 rounded-lg hover:bg-green-50">
                  Import từ Excel
                </button>
                <button className="w-full p-3 border border-yellow-300 text-yellow-700 rounded-lg hover:bg-yellow-50">
                  Xuất báo cáo SC
                </button>
                <button className="w-full p-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                  Xem bản đồ SC
                </button>
              </div>
            </Card>
          </div>
        </div>

        {/* Performance Overview */}
        <Card title="Theo dõi hiệu suất Service Centers">
          <EmptyState
            title="Biểu đồ hiệu suất SC"
            description="Số claim/tháng, tỉ lệ hoàn thành, chi phí trung bình từng SC"
            action={<div className="text-xs text-gray-400">Performance charts sẽ được thêm vào đây</div>}
          />
        </Card>
      </PageContainer>
    </BaseLayout>
  );
}