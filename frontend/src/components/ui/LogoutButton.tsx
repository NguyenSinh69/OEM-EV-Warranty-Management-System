'use client';

import React from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { ArrowLeftOnRectangleIcon } from '@heroicons/react/24/outline';

interface LogoutButtonProps {
  variant?: 'primary' | 'secondary' | 'minimal';
  size?: 'sm' | 'md' | 'lg';
  showIcon?: boolean;
  showText?: boolean;
  className?: string;
}

export default function LogoutButton({ 
  variant = 'secondary',
  size = 'md',
  showIcon = true,
  showText = true,
  className = ''
}: LogoutButtonProps) {
  const { logout } = useAuth();

  const handleLogout = () => {
    logout();
    window.location.href = '/login';
  };

  const baseClasses = 'inline-flex items-center font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
  
  const variantClasses = {
    primary: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-indigo-500',
    minimal: 'text-gray-700 hover:text-gray-900 hover:bg-gray-100'
  };

  const sizeClasses = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-3 py-2 text-sm',
    lg: 'px-4 py-2 text-base'
  };

  const iconSizes = {
    sm: 'h-3 w-3',
    md: 'h-4 w-4', 
    lg: 'h-5 w-5'
  };

  return (
    <button
      onClick={handleLogout}
      className={`
        ${baseClasses}
        ${variantClasses[variant]}
        ${sizeClasses[size]}
        ${className}
      `}
    >
      {showIcon && (
        <ArrowLeftOnRectangleIcon 
          className={`${iconSizes[size]} ${showText ? 'mr-1' : ''}`} 
        />
      )}
      {showText && 'Đăng xuất'}
    </button>
  );
}