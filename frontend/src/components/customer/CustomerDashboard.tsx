'use client';

import BaseLayout from '@/components/layout/BaseLayout';
import { PageContainer, Card, EmptyState } from '@/components/ui';
import { 
  TruckIcon,
  ClipboardDocumentListIcon,
  BellIcon,
  CalendarIcon
} from '@heroicons/react/24/outline';

const customerNavigation = [
  { name: 'My Vehicles', href: '/customer', icon: TruckIcon, current: true },
  { name: 'My Warranty Claims', href: '/customer/claims', icon: ClipboardDocumentListIcon },
  { name: 'Notifications', href: '/customer/notifications', icon: BellIcon },
  { name: 'Booking', href: '/customer/booking', icon: CalendarIcon },
];

export default function CustomerDashboard() {
  return (
    <BaseLayout
      navigation={customerNavigation}
      userRole="customer"
      headerTitle="My Vehicles"
      headerSubtitle="Thông tin xe và bảo hành của tôi"
    >
      <PageContainer>
        {/* Vehicle Overview */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card title="Tổng số xe" className="text-center">
            <div className="text-3xl font-bold text-blue-600">2</div>
            <p className="text-sm text-gray-500">Xe sở hữu</p>
          </Card>
          
          <Card title="Bảo hành còn lại" className="text-center">
            <div className="text-3xl font-bold text-green-600">18</div>
            <p className="text-sm text-gray-500">Tháng</p>
          </Card>
          
          <Card title="Claims đã tạo" className="text-center">
            <div className="text-3xl font-bold text-orange-600">3</div>
            <p className="text-sm text-gray-500">Tổng cộng</p>
          </Card>
        </div>

        {/* Vehicle List */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <Card title="VinFast VF8 - VF3ABCDEF12345678">
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Màu sắc:</span>
                <span className="font-medium">Đen Kim Cương</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Ngày mua:</span>
                <span className="font-medium">15/01/2024</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Bảo hành đến:</span>
                <span className="font-medium text-green-600">15/01/2026</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Service Center:</span>
                <span className="font-medium">VinFast SC Hà Nội</span>
              </div>
              <button className="w-full mt-4 p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Xem chi tiết bảo hành
              </button>
            </div>
          </Card>
          
          <Card title="VinFast VF5 - VF3MNOPQR98765432">
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Màu sắc:</span>
                <span className="font-medium">Xanh Dương</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Ngày mua:</span>
                <span className="font-medium">10/03/2024</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Bảo hành đến:</span>
                <span className="font-medium text-green-600">10/03/2026</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Service Center:</span>
                <span className="font-medium">VinFast SC Hà Nội</span>
              </div>
              <button className="w-full mt-4 p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Xem chi tiết bảo hành
              </button>
            </div>
          </Card>
        </div>

        {/* Quick Actions */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card title="Thông báo mới nhất">
            <EmptyState
              title="Recent Notifications"
              description="Thông báo recall, bảo dưỡng định kỳ, claim hoàn tất"
              action={<div className="text-xs text-gray-400">Notifications list sẽ được thêm vào đây</div>}
            />
          </Card>
          
          <Card title="Thao tác nhanh">
            <div className="space-y-3">
              <button className="w-full p-3 bg-blue-50 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-100">
                Xem warranty claims
              </button>
              <button className="w-full p-3 bg-green-50 border border-green-300 text-green-700 rounded-lg hover:bg-green-100">
                Đặt lịch bảo dưỡng
              </button>
              <button className="w-full p-3 bg-purple-50 border border-purple-300 text-purple-700 rounded-lg hover:bg-purple-100">
                Liên hệ Service Center
              </button>
              <button className="w-full p-3 bg-gray-50 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100">
                Tải ứng dụng VinFast
              </button>
            </div>
          </Card>
        </div>
      </PageContainer>
    </BaseLayout>
  );
}