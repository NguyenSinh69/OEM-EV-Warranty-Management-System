'use client';

import BaseLayout from '@/components/layout/BaseLayout';
import { PageContainer, Card, EmptyState } from '@/components/ui';
import { 
  TruckIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  UserGroupIcon
} from '@heroicons/react/24/outline';

const scStaffNavigation = [
  { name: 'Vehicle Registration', href: '/sc-staff', icon: TruckIcon, current: true },
  { name: 'Claim Management', href: '/sc-staff/claims', icon: ClipboardDocumentListIcon },
  { name: 'Parts Inventory', href: '/sc-staff/inventory', icon: CubeIcon },
  { name: 'Technician Assignment', href: '/sc-staff/technicians', icon: UserGroupIcon },
];

export default function SCStaffDashboard() {
  return (
    <BaseLayout
      navigation={scStaffNavigation}
      userRole="sc_staff"
      headerTitle="Vehicle Registration"
      headerSubtitle="Đăng ký xe mới và quản lý thông tin xe"
      headerActions={
        <button className="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
          Đăng ký xe mới
        </button>
      }
    >
      <PageContainer>
        {/* Registration Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card title="Xe đã đăng ký" className="text-center">
            <div className="text-3xl font-bold text-blue-600">1,234</div>
            <p className="text-sm text-gray-500">Tổng cộng</p>
          </Card>
          
          <Card title="Đăng ký hôm nay" className="text-center">
            <div className="text-3xl font-bold text-green-600">5</div>
            <p className="text-sm text-gray-500">Xe mới</p>
          </Card>
          
          <Card title="VF8" className="text-center">
            <div className="text-3xl font-bold text-purple-600">678</div>
            <p className="text-sm text-gray-500">Xe đã đăng ký</p>
          </Card>
          
          <Card title="VF9" className="text-center">
            <div className="text-3xl font-bold text-orange-600">456</div>
            <p className="text-sm text-gray-500">Xe đã đăng ký</p>
          </Card>
        </div>

        {/* Vehicle Registration Form */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <div className="lg:col-span-2">
            <Card title="Đăng ký xe mới">
              <EmptyState
                title="Form đăng ký xe"
                description="Nhập VIN, khách hàng, ngày mua, thời hạn bảo hành"
                action={
                  <div className="space-y-2 text-xs text-gray-400">
                    <p>• Tự động gắn thông tin xe & khách hàng</p>
                    <p>• Form validation VIN number</p>
                    <p>• Registration form sẽ được thêm vào đây</p>
                  </div>
                }
              />
            </Card>
          </div>
          
          <div>
            <Card title="Tìm kiếm xe">
              <div className="space-y-4">
                <input 
                  type="text" 
                  placeholder="Nhập VIN..." 
                  className="w-full p-3 border border-gray-300 rounded-lg"
                />
                <input 
                  type="text" 
                  placeholder="Tên khách hàng..." 
                  className="w-full p-3 border border-gray-300 rounded-lg"
                />
                <input 
                  type="text" 
                  placeholder="Biển số xe..." 
                  className="w-full p-3 border border-gray-300 rounded-lg"
                />
                <button className="w-full p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                  Tìm kiếm
                </button>
              </div>
            </Card>
          </div>
        </div>

        {/* Recently Registered Vehicles */}
        <Card title="Xe đã đăng ký gần đây">
          <EmptyState
            title="Danh sách xe đăng ký"
            description="Danh sách xe đã đăng ký với thông tin khách hàng"
            action={<div className="text-xs text-gray-400">Vehicle table sẽ được thêm vào đây</div>}
          />
        </Card>
      </PageContainer>
    </BaseLayout>
  );
}