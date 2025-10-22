'use client';

import BaseLayout from '@/components/layout/BaseLayout';
import { PageContainer, Card, EmptyState } from '@/components/ui';
import { 
  ChartBarIcon,
  UserGroupIcon,
  BuildingOfficeIcon,
  PlusIcon
} from '@heroicons/react/24/outline';

const adminNavigation = [
  { name: 'Dashboard', href: '/admin', icon: ChartBarIcon },
  { name: 'Account & Role Management', href: '/admin/accounts', icon: UserGroupIcon, current: true },
  { name: 'Service Center Management', href: '/admin/service-centers', icon: BuildingOfficeIcon },
];

export default function AccountManagement() {
  return (
    <BaseLayout
      navigation={adminNavigation}
      userRole="admin"
      headerTitle="Account & Role Management"
      headerSubtitle="Quản lý tài khoản và phân quyền hệ thống"
      headerActions={
        <button className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
          <PlusIcon className="h-5 w-5 mr-2" />
          Tạo tài khoản mới
        </button>
      }
    >
      <PageContainer>
        {/* Role Statistics */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card title="EVM Staff" className="text-center">
            <div className="text-2xl font-bold text-green-600">12</div>
            <p className="text-sm text-gray-500">Nhân viên hãng</p>
          </Card>
          
          <Card title="SC Staff" className="text-center">
            <div className="text-2xl font-bold text-purple-600">45</div>
            <p className="text-sm text-gray-500">Nhân viên SC</p>
          </Card>
          
          <Card title="Technician" className="text-center">
            <div className="text-2xl font-bold text-orange-600">89</div>
            <p className="text-sm text-gray-500">Kỹ thuật viên</p>
          </Card>
          
          <Card title="Customer" className="text-center">
            <div className="text-2xl font-bold text-indigo-600">890</div>
            <p className="text-sm text-gray-500">Khách hàng</p>
          </Card>
        </div>

        {/* Account Management Section */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2">
            <Card title="Danh sách tài khoản">
              <EmptyState
                title="Bảng quản lý tài khoản"
                description="Tạo / xoá / chỉnh sửa tài khoản (EVM Staff, SC Staff, Technician)"
                action={
                  <div className="space-y-2 text-xs text-gray-400">
                    <p>• Gán vai trò, quyền truy cập (role-based access)</p>
                    <p>• Reset mật khẩu</p>
                    <p>• Bảng dữ liệu sẽ được thêm vào đây</p>
                  </div>
                }
              />
            </Card>
          </div>
          
          <div>
            <Card title="Thao tác nhanh">
              <div className="space-y-4">
                <button className="w-full p-3 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50">
                  Tạo EVM Staff
                </button>
                <button className="w-full p-3 border border-purple-300 text-purple-700 rounded-lg hover:bg-purple-50">
                  Tạo SC Staff
                </button>
                <button className="w-full p-3 border border-orange-300 text-orange-700 rounded-lg hover:bg-orange-50">
                  Tạo Technician
                </button>
                <button className="w-full p-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                  Import từ Excel
                </button>
              </div>
            </Card>
          </div>
        </div>
      </PageContainer>
    </BaseLayout>
  );
}