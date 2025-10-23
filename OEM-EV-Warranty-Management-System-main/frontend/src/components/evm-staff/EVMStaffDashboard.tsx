'use client';

import BaseLayout from '@/components/layout/BaseLayout';
import { PageContainer, Card, EmptyState } from '@/components/ui';
import { 
  ChartBarIcon,
  ClipboardDocumentListIcon,
  Cog6ToothIcon,
  CubeIcon,
  BellIcon
} from '@heroicons/react/24/outline';

const evmNavigation = [
  { name: 'Dashboard', href: '/evm-staff', icon: ChartBarIcon, current: true },
  { name: 'Claim Management', href: '/evm-staff/claims', icon: ClipboardDocumentListIcon },
  { name: 'Policy Management', href: '/evm-staff/policy', icon: Cog6ToothIcon },
  { name: 'Parts & Inventory', href: '/evm-staff/inventory', icon: CubeIcon },
  { name: 'Recall Management', href: '/evm-staff/recall', icon: BellIcon },
];

export default function EVMStaffDashboard() {
  return (
    <BaseLayout
      navigation={evmNavigation}
      userRole="evm_staff"
      headerTitle="EVM Staff Dashboard"
      headerSubtitle="Quản lý bảo hành và chính sách hãng xe"
      headerActions={
        <button className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
          Refresh Data
        </button>
      }
    >
      <PageContainer>
        {/* Claims Overview */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card title="Claims chờ duyệt" className="text-center">
            <div className="text-3xl font-bold text-yellow-600">23</div>
            <p className="text-sm text-gray-500">Cần xem xét</p>
          </Card>
          
          <Card title="Claims đang xử lý" className="text-center">
            <div className="text-3xl font-bold text-blue-600">67</div>
            <p className="text-sm text-gray-500">Trong tiến trình</p>
          </Card>
          
          <Card title="Claims hoàn tất" className="text-center">
            <div className="text-3xl font-bold text-green-600">145</div>
            <p className="text-sm text-gray-500">Tháng này</p>
          </Card>
          
          <Card title="Tỷ lệ duyệt" className="text-center">
            <div className="text-3xl font-bold text-purple-600">87%</div>
            <p className="text-sm text-gray-500">30 ngày qua</p>
          </Card>
        </div>

        {/* Charts & Reports */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <Card title="Thống kê Claims theo tháng">
            <EmptyState
              title="Biểu đồ Claims Timeline"
              description="Theo dõi xu hướng claims theo thời gian"
              action={<div className="text-xs text-gray-400">Line chart sẽ được thêm vào đây</div>}
            />
          </Card>
          
          <Card title="Lỗi phổ biến theo model xe">
            <EmptyState
              title="Biểu đồ Failure Analysis"
              description="Phân tích lỗi phổ biến theo từng dòng xe"
              action={<div className="text-xs text-gray-400">Bar chart sẽ được thêm vào đây</div>}
            />
          </Card>
        </div>

        {/* Quick Actions */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <Card title="Claims cần xử lý ngay">
            <EmptyState
              title="High Priority Claims"
              description="Danh sách claims ưu tiên cao cần duyệt"
              action={<div className="text-xs text-gray-400">Claims table sẽ được thêm vào đây</div>}
            />
          </Card>
          
          <Card title="Chi phí bảo hành">
            <div className="space-y-4">
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Tháng này</span>
                <span className="font-semibold">2.4 tỷ VNĐ</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">Trung bình/claim</span>
                <span className="font-semibold">15.2 triệu VNĐ</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-gray-600">So với tháng trước</span>
                <span className="font-semibold text-green-600">-8.5%</span>
              </div>
            </div>
          </Card>
          
          <Card title="Thao tác nhanh">
            <div className="space-y-3">
              <button className="w-full p-3 bg-yellow-50 border border-yellow-300 text-yellow-700 rounded-lg hover:bg-yellow-100">
                Duyệt Claims (23)
              </button>
              <button className="w-full p-3 bg-blue-50 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-100">
                Quản lý Policy
              </button>
              <button className="w-full p-3 bg-red-50 border border-red-300 text-red-700 rounded-lg hover:bg-red-100">
                Tạo Recall Notice
              </button>
              <button className="w-full p-3 bg-gray-50 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100">
                Xuất báo cáo
              </button>
            </div>
          </Card>
        </div>
      </PageContainer>
    </BaseLayout>
  );
}