'use client';

import BaseLayout from '@/components/layout/BaseLayout';
import { PageContainer, Card, EmptyState, LogoutButton, UserProfile } from '@/components/ui';
import { 
  ChartBarIcon,
  UserGroupIcon,
  BuildingOfficeIcon
} from '@heroicons/react/24/outline';

const adminNavigation = [
  { name: 'Dashboard', href: '/admin', icon: ChartBarIcon, current: true },
  { name: 'Account & Role Management', href: '/admin/accounts', icon: UserGroupIcon },
  { name: 'Service Center Management', href: '/admin/service-centers', icon: BuildingOfficeIcon },
];

export default function AdminDashboard() {
  return (
    <BaseLayout
      navigation={adminNavigation}
      userRole="admin"
      headerTitle="Admin Dashboard"
      headerSubtitle="Tổng quan hệ thống EVM Warranty Management"
      headerActions={
        <div className="flex items-center space-x-4">
          <button className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
            Refresh Data
          </button>
          <div className="h-6 border-l border-gray-300"></div>
          <UserProfile showAvatar={false} />
          <LogoutButton variant="secondary" size="md" />
        </div>
      }
    >
      <PageContainer>
        {/* Stats Overview */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <Card title="Tổng số xe" className="text-center">
            <div className="text-3xl font-bold text-blue-600">1,250</div>
            <p className="text-sm text-gray-500 mt-2">+12% so với tháng trước</p>
          </Card>
          
          <Card title="Trung tâm dịch vụ" className="text-center">
            <div className="text-3xl font-bold text-green-600">15</div>
            <p className="text-sm text-gray-500 mt-2">Hoạt động</p>
          </Card>
          
          <Card title="Nhân sự" className="text-center">
            <div className="text-3xl font-bold text-purple-600">234</div>
            <p className="text-sm text-gray-500 mt-2">Tổng nhân viên</p>
          </Card>
          
          <Card title="Claims đang xử lý" className="text-center">
            <div className="text-3xl font-bold text-orange-600">45</div>
            <p className="text-sm text-gray-500 mt-2">Cần xem xét</p>
          </Card>
        </div>

        {/* Charts Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <Card title="Thống kê Claims theo trạng thái">
            <EmptyState
              title="Biểu đồ Claims"
              description="Hiển thị phân bố claims theo open, approved, done..."
              action={<div className="text-xs text-gray-400">Chart component sẽ được thêm vào đây</div>}
            />
          </Card>
          
          <Card title="Claims theo Service Center">
            <EmptyState
              title="Biểu đồ SC Performance"
              description="Số lượng claims từng SC / từng dòng xe"
              action={<div className="text-xs text-gray-400">Chart component sẽ được thêm vào đây</div>}
            />
          </Card>
        </div>

        {/* Quick Actions */}
        <Card title="Thao tác nhanh">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 text-left">
              <UserGroupIcon className="h-6 w-6 text-blue-600 mb-2" />
              <h3 className="font-medium">Quản lý tài khoản</h3>
              <p className="text-sm text-gray-500">Tạo, sửa, xóa tài khoản</p>
            </button>
            
            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 text-left">
              <BuildingOfficeIcon className="h-6 w-6 text-green-600 mb-2" />
              <h3 className="font-medium">Quản lý Service Center</h3>
              <p className="text-sm text-gray-500">Thêm, cập nhật SC</p>
            </button>
            
            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 text-left">
              <ChartBarIcon className="h-6 w-6 text-purple-600 mb-2" />
              <h3 className="font-medium">Báo cáo hệ thống</h3>
              <p className="text-sm text-gray-500">Xuất báo cáo tổng hợp</p>
            </button>
          </div>
        </Card>
      </PageContainer>
    </BaseLayout>
  );
}