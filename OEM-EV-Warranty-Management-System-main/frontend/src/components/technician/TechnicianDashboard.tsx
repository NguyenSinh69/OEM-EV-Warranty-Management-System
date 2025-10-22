'use client';

import BaseLayout from '@/components/layout/BaseLayout';
import { PageContainer, Card, EmptyState } from '@/components/ui';
import { 
  QueueListIcon,
  WrenchScrewdriverIcon,
  DocumentTextIcon
} from '@heroicons/react/24/outline';

const technicianNavigation = [
  { name: 'Claim Queue', href: '/technician', icon: QueueListIcon, current: true },
  { name: 'Diagnosis & Repair', href: '/technician/repair', icon: WrenchScrewdriverIcon },
  { name: 'Work Log', href: '/technician/work-log', icon: DocumentTextIcon },
];

export default function TechnicianDashboard() {
  return (
    <BaseLayout
      navigation={technicianNavigation}
      userRole="technician"
      headerTitle="Claim Queue"
      headerSubtitle="Danh sách công việc được gán"
      headerActions={
        <button className="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">
          Refresh Queue
        </button>
      }
    >
      <PageContainer>
        {/* Work Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card title="Claims được gán" className="text-center">
            <div className="text-3xl font-bold text-blue-600">8</div>
            <p className="text-sm text-gray-500">Cần xử lý</p>
          </Card>
          
          <Card title="Đang thực hiện" className="text-center">
            <div className="text-3xl font-bold text-yellow-600">3</div>
            <p className="text-sm text-gray-500">Claims</p>
          </Card>
          
          <Card title="Hoàn thành hôm nay" className="text-center">
            <div className="text-3xl font-bold text-green-600">2</div>
            <p className="text-sm text-gray-500">Claims</p>
          </Card>
          
          <Card title="Thời gian trung bình" className="text-center">
            <div className="text-3xl font-bold text-purple-600">4.2h</div>
            <p className="text-sm text-gray-500">Per claim</p>
          </Card>
        </div>

        {/* Active Claims */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <div className="lg:col-span-2">
            <Card title="Claims được gán cho tôi">
              <EmptyState
                title="Work Queue"
                description="Danh sách claims được gán, lọc theo trạng thái hoặc ngày nhận"
                action={
                  <div className="space-y-2 text-xs text-gray-400">
                    <p>• Priority-based sorting</p>
                    <p>• Status filtering</p>
                    <p>• Claims table sẽ được thêm vào đây</p>
                  </div>
                }
              />
            </Card>
          </div>
          
          <div>
            <Card title="Thao tác nhanh">
              <div className="space-y-3">
                <button className="w-full p-3 bg-blue-50 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-100">
                  Bắt đầu chẩn đoán
                </button>
                <button className="w-full p-3 bg-yellow-50 border border-yellow-300 text-yellow-700 rounded-lg hover:bg-yellow-100">
                  Cập nhật tiến độ
                </button>
                <button className="w-full p-3 bg-green-50 border border-green-300 text-green-700 rounded-lg hover:bg-green-100">
                  Hoàn thành công việc
                </button>
                <button className="w-full p-3 bg-gray-50 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100">
                  Xem work log
                </button>
              </div>
            </Card>
          </div>
        </div>

        {/* Today's Schedule */}
        <Card title="Lịch làm việc hôm nay">
          <EmptyState
            title="Today's Work Schedule"
            description="Timeline các công việc trong ngày"
            action={<div className="text-xs text-gray-400">Schedule timeline sẽ được thêm vào đây</div>}
          />
        </Card>
      </PageContainer>
    </BaseLayout>
  );
}