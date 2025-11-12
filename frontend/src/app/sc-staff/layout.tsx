'use client';

import { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import {
  TruckIcon,
  DocumentTextIcon,
  CubeIcon,
  UserGroupIcon,
  Bars3Icon,
  XMarkIcon,
  UserCircleIcon,
  ArrowRightOnRectangleIcon,
  HomeIcon,
  ListBulletIcon
} from '@heroicons/react/24/outline';

interface SidebarItemProps {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  href: string;
  isActive: boolean;
}

function SidebarItem({ icon: Icon, label, href, isActive }: SidebarItemProps) {
  return (
    <Link
      href={href}
      className={`flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors ${
        isActive
          ? 'bg-purple-100 text-purple-900 border-r-2 border-purple-600'
          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
      }`}
    >
      <Icon className="mr-3 h-5 w-5" />
      {label}
    </Link>
  );
}

export default function SCStaffLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const pathname = usePathname();
  const { logout, user } = useAuth();

  const sidebarItems = [
    {
      icon: HomeIcon,
      label: 'Dashboard',
      href: '/sc-staff',
    },
    {
      icon: TruckIcon,
      label: 'Đăng Ký Xe',
      href: '/sc-staff/vehicle-registration',
    },
    {
      icon: ListBulletIcon,
      label: 'Danh Sách Xe',
      href: '/sc-staff/vehicles',
    },
    {
      icon: DocumentTextIcon,
      label: 'Quản Lý Yêu Cầu',
      href: '/sc-staff/claim-management',
    },
    {
      icon: CubeIcon,
      label: 'Quản Lý Kho',
      href: '/sc-staff/parts-inventory',
    },
    {
      icon: UserGroupIcon,
      label: 'Phân Công Kỹ Thuật',
      href: '/sc-staff/technician-assignment',
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <div
        className={`fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 ${
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Header */}
        <div className="flex items-center justify-between h-16 px-4 bg-gradient-to-r from-purple-600 to-purple-700">
          <div className="flex items-center">
            <div className="text-white font-bold text-lg">EVM Warranty</div>
          </div>
          <button
            className="lg:hidden text-white hover:text-gray-200"
            onClick={() => setSidebarOpen(false)}
          >
            <XMarkIcon className="h-6 w-6" />
          </button>
        </div>
        
          <div className="px-4 py-2">
          <div className="text-sm text-gray-500 font-medium">Trung Tâm Dịch Vụ</div>
        </div>        {/* Navigation */}
        <nav className="mt-4 px-4 space-y-1">
          {sidebarItems.map((item) => (
            <SidebarItem
              key={item.href}
              icon={item.icon}
              label={item.label}
              href={item.href}
              isActive={pathname === item.href}
            />
          ))}
        </nav>

        {/* User info at bottom */}
        <div className="absolute bottom-0 left-0 right-0 p-4 border-t bg-gray-50">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <UserCircleIcon className="h-8 w-8 text-gray-400" />
              <div className="ml-3">
                <div className="text-sm font-medium text-gray-700">
                  {user?.name || 'SC Staff'}
                </div>
                <div className="text-xs text-gray-500">Nhân viên trung tâm</div>
              </div>
            </div>
            <button
              onClick={() => {
                console.log('Logout button clicked!');
                logout();
              }}
              className="flex items-center px-2 py-1 text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors cursor-pointer"
              title="Đăng xuất"
            >
              <ArrowRightOnRectangleIcon className="h-4 w-4 mr-1" />
              <span className="text-xs">Đăng Xuất</span>
            </button>
          </div>
        </div>
      </div>

      {/* Main content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Mobile header */}
        <div className="lg:hidden">
          <div className="flex items-center justify-between h-16 px-4 bg-white shadow-sm">
            <button
              className="text-gray-500 hover:text-gray-700"
              onClick={() => setSidebarOpen(true)}
            >
              <Bars3Icon className="h-6 w-6" />
            </button>
            <div className="font-semibold text-gray-900">EVM Warranty System</div>
            <button
              onClick={() => {
                console.log('Mobile logout clicked!');
                logout();
              }}
              className="text-gray-500 hover:text-red-600 p-1"
              title="Đăng xuất"
            >
              <ArrowRightOnRectangleIcon className="h-6 w-6" />
            </button>
          </div>
        </div>

        {/* Page content */}
        <main className="flex-1 overflow-y-auto">
          {children}
        </main>
      </div>
    </div>
  );
}