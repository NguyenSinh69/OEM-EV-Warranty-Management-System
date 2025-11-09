"use client";

import React, { useState, useEffect } from "react";
import BaseLayout from "@/components/layout/BaseLayout";
import {
  PageContainer,
  Card,
  EmptyState,
  LogoutButton,
  UserProfile,
} from "@/components/ui";
import {
  ChartBarIcon,
  UserGroupIcon,
  BuildingOfficeIcon,
} from "@heroicons/react/24/outline";
import { api } from "@/lib/api";

// Add type declarations to fix JSX issues
declare global {
  namespace JSX {
    interface IntrinsicElements {
      div: any;
      p: any;
      button: any;
      span: any;
      h1: any;
      h2: any;
      h3: any;
    }
  }
}

const adminNavigation = [
  { name: "Dashboard", href: "/admin", icon: ChartBarIcon, current: true },
  {
    name: "Account & Role Management",
    href: "/admin/accounts",
    icon: UserGroupIcon,
  },
  {
    name: "Service Center Management",
    href: "/admin/service-centers",
    icon: BuildingOfficeIcon,
  },
];

interface DashboardStats {
  total_users: number;
  total_service_centers: number;
  total_warranties: number;
  total_cost: number;
}

interface AnalyticsData {
  failures: Array<{ failure_type: string; count: number }>;
  costs: Array<{ month: string; total: number }>;
  performance: Array<{ service_center: string; total_claims: number }>;
}

export default function AdminDashboard() {
  const [stats, setStats] = useState<DashboardStats>({
    total_users: 0,
    total_service_centers: 0,
    total_warranties: 0,
    total_cost: 0,
  });
  const [analytics, setAnalytics] = useState<AnalyticsData>({
    failures: [],
    costs: [],
    performance: [],
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Fetch dashboard stats
      const statsResponse = await api.getDashboardStats();
      if (statsResponse.success) {
        setStats(statsResponse.data);
      }

      // Fetch analytics data
      const [failuresResponse, costsResponse, performanceResponse] =
        await Promise.all([
          api.getFailureAnalytics(),
          api.getCostAnalytics(),
          api.getPerformanceAnalytics(),
        ]);

      setAnalytics({
        failures: failuresResponse.success ? failuresResponse.data : [],
        costs: costsResponse.success ? costsResponse.data : [],
        performance: performanceResponse.success
          ? performanceResponse.data
          : [],
      });
    } catch (err) {
      console.error("Error fetching dashboard data:", err);
      setError("Không thể tải dữ liệu dashboard. Vui lòng thử lại.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(amount);
  };

  const formatNumber = (num: number) => {
    return new Intl.NumberFormat("vi-VN").format(num);
  };

  if (error) {
    return (
      <BaseLayout
        navigation={adminNavigation}
        userRole="admin"
        headerTitle="Admin Dashboard"
        headerSubtitle="Tổng quan hệ thống EVM Warranty Management"
      >
        <PageContainer>
          <Card title="Lỗi">
            <div className="text-red-600 p-4 text-center">
              <p>{error}</p>
              <button
                onClick={fetchDashboardData}
                className="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
              >
                Thử lại
              </button>
            </div>
          </Card>
        </PageContainer>
      </BaseLayout>
      </BaseLayout>
    );
  }

  return (
    <BaseLayout
      navigation={adminNavigation}
      userRole="admin"
      headerTitle="Admin Dashboard"
      headerSubtitle="Tổng quan hệ thống EVM Warranty Management"
      headerActions={
        <div className="flex items-center space-x-4">
          <button
            onClick={fetchDashboardData}
            disabled={loading}
            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors disabled:bg-gray-400"
          >
            {loading ? "Đang tải..." : "Refresh Data"}
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
          <Card title="Tổng số người dùng" className="text-center">
            <div className="text-3xl font-bold text-blue-600">
              {loading ? "..." : formatNumber(stats.total_users)}
            </div>
            <p className="text-sm text-gray-500 mt-2">Trong hệ thống</p>
          </Card>

          <Card title="Trung tâm dịch vụ" className="text-center">
            <div className="text-3xl font-bold text-green-600">
              {loading ? "..." : formatNumber(stats.total_service_centers)}
            </div>
            <p className="text-sm text-gray-500 mt-2">Đang hoạt động</p>
          </Card>

          <Card title="Warranty Claims" className="text-center">
            <div className="text-3xl font-bold text-purple-600">
              {loading ? "..." : formatNumber(stats.total_warranties)}
            </div>
            <p className="text-sm text-gray-500 mt-2">Tổng số claims</p>
          </Card>

          <Card title="Tổng chi phí sửa chữa" className="text-center">
            <div className="text-3xl font-bold text-orange-600">
              {loading ? "..." : formatCurrency(stats.total_cost)}
            </div>
            <p className="text-sm text-gray-500 mt-2">Đã chi trả</p>
          </Card>
        </div>

        {/* Analytics Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <Card title="Thống kê lỗi theo loại linh kiện">
            {loading ? (
              <div className="p-4 text-center text-gray-500">Đang tải...</div>
            ) : analytics.failures.length > 0 ? (
              <div className="space-y-3">
                {analytics.failures.map((item, index) => (
                  <div
                    key={index}
                    className="flex justify-between items-center"
                  >
                    <span className="font-medium">{item.failure_type}</span>
                    <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm">
                      {item.count}
                    </span>
                  </div>
                ))}
              </div>
            ) : (
              <EmptyState
                title="Chưa có dữ liệu"
                description="Chưa có thông tin về lỗi linh kiện"
              />
            )}
          </Card>

          <Card title="Hiệu suất Service Centers">
            {loading ? (
              <div className="p-4 text-center text-gray-500">Đang tải...</div>
            ) : analytics.performance.length > 0 ? (
              <div className="space-y-3">
                {analytics.performance.map((item, index) => (
                  <div
                    key={index}
                    className="flex justify-between items-center"
                  >
                    <span className="font-medium">{item.service_center}</span>
                    <span className="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                      {item.total_claims} claims
                    </span>
                  </div>
                ))}
              </div>
            ) : (
              <EmptyState
                title="Chưa có dữ liệu"
                description="Chưa có thông tin về hiệu suất service centers"
              />
            )}
          </Card>
        </div>

        {/* Cost Analytics */}
        {analytics.costs.length > 0 && (
          <div className="mb-8">
            <Card title="Chi phí sửa chữa theo tháng">
              <div className="space-y-3">
                {analytics.costs.map((item, index) => (
                  <div
                    key={index}
                    className="flex justify-between items-center"
                  >
                    <span className="font-medium">{item.month}</span>
                    <span className="text-lg font-bold text-orange-600">
                      {formatCurrency(item.total)}
                    </span>
                  </div>
                ))}
              </div>
            </Card>
          </div>
        )}

        {/* Quick Actions */}
        <Card title="Thao tác nhanh">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 text-left transition-colors">
              <UserGroupIcon className="h-6 w-6 text-blue-600 mb-2" />
              <h3 className="font-medium">Quản lý tài khoản</h3>
              <p className="text-sm text-gray-500">Tạo, sửa, xóa tài khoản</p>
            </button>

            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 text-left transition-colors">
              <BuildingOfficeIcon className="h-6 w-6 text-green-600 mb-2" />
              <h3 className="font-medium">Quản lý Service Center</h3>
              <p className="text-sm text-gray-500">Thêm, cập nhật SC</p>
            </button>

            <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 text-left transition-colors">
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
