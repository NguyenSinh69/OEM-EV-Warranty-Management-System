'use client';

import React from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { UserCircleIcon } from '@heroicons/react/24/outline';

interface UserProfileProps {
  showAvatar?: boolean;
  showRole?: boolean;
  className?: string;
}

export default function UserProfile({ 
  showAvatar = true,
  showRole = true,
  className = ''
}: UserProfileProps) {
  const { user } = useAuth();

  if (!user) return null;

  const roleNames = {
    admin: 'Quản trị viên',
    evm_staff: 'Nhân viên EVM',
    sc_staff: 'Nhân viên SC',
    technician: 'Kỹ thuật viên',
    customer: 'Khách hàng'
  };

  const roleColors = {
    admin: 'text-blue-600 bg-blue-50',
    evm_staff: 'text-green-600 bg-green-50',
    sc_staff: 'text-purple-600 bg-purple-50',
    technician: 'text-orange-600 bg-orange-50',
    customer: 'text-indigo-600 bg-indigo-50'
  };

  return (
    <div className={`flex items-center space-x-3 ${className}`}>
      {showAvatar && (
        <div className="flex-shrink-0">
          <UserCircleIcon className="h-8 w-8 text-gray-400" />
        </div>
      )}
      <div className="text-right">
        <p className="text-sm font-medium text-gray-900">{user.name}</p>
        <div className="flex items-center space-x-2">
          <p className="text-xs text-gray-500">{user.email}</p>
          {showRole && (
            <span className={`
              inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
              ${roleColors[user.role as keyof typeof roleColors] || 'text-gray-600 bg-gray-50'}
            `}>
              {roleNames[user.role as keyof typeof roleNames] || user.role}
            </span>
          )}
        </div>
      </div>
    </div>
  );
}