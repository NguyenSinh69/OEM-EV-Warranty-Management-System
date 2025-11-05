import React, { useState, useEffect } from 'react';
import { BellIcon } from '@heroicons/react/24/outline';
import { BellIcon as BellSolidIcon } from '@heroicons/react/24/solid';
import NotificationCenter from './NotificationCenter';

interface NotificationBellProps {
  customerId: number;
  className?: string;
}

export default function NotificationBell({ customerId, className = "" }: NotificationBellProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (customerId) {
      fetchUnreadCount();
      
      // Poll for new notifications every 30 seconds
      const interval = setInterval(fetchUnreadCount, 30000);
      return () => clearInterval(interval);
    }
  }, [customerId]);

  const fetchUnreadCount = async () => {
    try {
      setLoading(true);
      const response = await fetch(`http://localhost:8005/api/notifications/${customerId}?unread_only=true&limit=1`);
      const data = await response.json();

      if (data.success) {
        setUnreadCount(data.data.unread_count || 0);
      }
    } catch (error) {
      console.error('Failed to fetch unread count:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleClick = () => {
    setIsOpen(true);
  };

  const handleClose = () => {
    setIsOpen(false);
    // Refresh unread count when closing
    fetchUnreadCount();
  };

  return (
    <>
      <button
        onClick={handleClick}
        className={`relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors ${className}`}
        title="Xem thông báo"
      >
        {unreadCount > 0 ? (
          <BellSolidIcon className="h-6 w-6 text-blue-600" />
        ) : (
          <BellIcon className="h-6 w-6" />
        )}
        
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}

        {loading && (
          <span className="absolute -top-1 -right-1 h-2 w-2 bg-blue-400 rounded-full animate-pulse"></span>
        )}
      </button>

      <NotificationCenter
        customerId={customerId}
        isOpen={isOpen}
        onClose={handleClose}
      />
    </>
  );
}