'use client';

import React from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { 
  HomeIcon,
  UserGroupIcon,
  BuildingOfficeIcon,
  Cog6ToothIcon,
  ArrowLeftOnRectangleIcon
} from '@heroicons/react/24/outline';

interface SidebarProps {
  navigation: Array<{
    name: string;
    href: string;
    icon: React.ComponentType<any>;
    current?: boolean;
  }>;
  userRole: string;
}

export function Sidebar({ navigation, userRole }: SidebarProps) {
  const { logout } = useAuth();

  const roleColors = {
    admin: 'bg-blue-600',
    evm_staff: 'bg-green-600', 
    sc_staff: 'bg-purple-600',
    technician: 'bg-orange-600',
    customer: 'bg-indigo-600'
  };

  const roleNames = {
    admin: 'Admin Panel',
    evm_staff: 'EVM Staff Portal',
    sc_staff: 'Service Center',
    technician: 'Technician Tools',
    customer: 'Customer Portal'
  };

  return (
    <div className="flex flex-col w-64 bg-white shadow-lg">
      {/* Header */}
      <div className={`px-6 py-4 ${roleColors[userRole as keyof typeof roleColors] || 'bg-gray-600'}`}>
        <div className="flex items-center">
          <div className="text-white">
            <h1 className="text-lg font-semibold">EVM Warranty</h1>
            <p className="text-sm opacity-90">
              {roleNames[userRole as keyof typeof roleNames] || 'Portal'}
            </p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="mt-6 flex-1">
        <div className="px-3 space-y-1">
          {navigation.map((item) => {
            const Icon = item.icon;
            return (
              <a
                key={item.name}
                href={item.href}
                className={`flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                  item.current
                    ? 'bg-gray-100 text-gray-900'
                    : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                }`}
              >
                <Icon className="mr-3 h-5 w-5" />
                {item.name}
              </a>
            );
          })}
        </div>
      </nav>

      {/* Logout */}
      <div className="border-t border-gray-200 p-3">
        <button
          onClick={() => {
            logout();
            window.location.href = '/login';
          }}
          className="flex items-center w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 hover:text-gray-900 transition-colors"
        >
          <ArrowLeftOnRectangleIcon className="mr-3 h-5 w-5" />
          Đăng xuất
        </button>
      </div>
    </div>
  );
}

interface HeaderProps {
  title: string;
  subtitle?: string;
  children?: React.ReactNode;
}

export function Header({ title, subtitle, children }: HeaderProps) {
  const { user, logout } = useAuth();
  
  return (
    <div className="bg-white shadow-sm border-b border-gray-200">
      <div className="px-6 py-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
            {subtitle && (
              <p className="text-sm text-gray-600 mt-1">{subtitle}</p>
            )}
          </div>
          <div className="flex items-center space-x-4">
            {children}
          </div>
        </div>
      </div>
    </div>
  );
}

interface BaseLayoutProps {
  children: React.ReactNode;
  navigation: Array<{
    name: string;
    href: string;
    icon: React.ComponentType<any>;
    current?: boolean;
  }>;
  userRole: string;
  headerTitle: string;
  headerSubtitle?: string;
  headerActions?: React.ReactNode;
}

export default function BaseLayout({ 
  children, 
  navigation, 
  userRole, 
  headerTitle, 
  headerSubtitle,
  headerActions 
}: BaseLayoutProps) {
  return (
    <div className="flex h-screen bg-gray-50">
      <Sidebar navigation={navigation} userRole={userRole} />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header title={headerTitle} subtitle={headerSubtitle}>
          {headerActions}
        </Header>
        <main className="flex-1 overflow-y-auto">
          {children}
        </main>
      </div>
    </div>
  );
}