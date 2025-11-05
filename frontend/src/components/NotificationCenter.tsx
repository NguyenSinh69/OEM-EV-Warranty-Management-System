import React, { useState, useEffect } from 'react';
import { 
  BellIcon, 
  XMarkIcon,
  CheckIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  CheckCircleIcon
} from '@heroicons/react/24/outline';
import { api } from '@/lib/api';

interface Notification {
  id: number;
  customer_id: number;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'success' | 'error' | 'warranty_claim' | 'appointment' | 'maintenance' | 'campaign';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'pending' | 'sent' | 'delivered' | 'read' | 'failed';
  read_at: string | null;
  created_at: string;
  data?: any;
}

interface NotificationCenterProps {
  customerId: number;
  isOpen: boolean;
  onClose: () => void;
}

export default function NotificationCenter({ customerId, isOpen, onClose }: NotificationCenterProps) {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [filter, setFilter] = useState<'all' | 'unread' | 'read'>('all');
  const [typeFilter, setTypeFilter] = useState<string>('all');

  useEffect(() => {
    if (isOpen && customerId) {
      fetchNotifications();
    }
  }, [isOpen, customerId, filter, typeFilter]);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        limit: '20',
        ...(filter === 'unread' && { unread_only: 'true' }),
        ...(typeFilter !== 'all' && { type: typeFilter })
      });

      const response = await fetch(`http://localhost:8005/api/notifications/${customerId}?${params}`);
      const data = await response.json();

      if (data.success) {
        setNotifications(data.data.notifications);
        setUnreadCount(data.data.unread_count);
      }
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    } finally {
      setLoading(false);
    }
  };

  const markAsRead = async (notificationId: number) => {
    try {
      const response = await fetch(`http://localhost:8005/api/notifications/${notificationId}/read`, {
        method: 'PUT'
      });
      
      if (response.ok) {
        setNotifications(prev => prev.map(notif => 
          notif.id === notificationId 
            ? { ...notif, read_at: new Date().toISOString(), status: 'read' }
            : notif
        ));
        setUnreadCount(prev => Math.max(0, prev - 1));
      }
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    }
  };

  const markAllAsRead = async () => {
    const unreadNotifications = notifications.filter(n => !n.read_at);
    
    for (const notification of unreadNotifications) {
      await markAsRead(notification.id);
    }
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'warning':
        return <ExclamationTriangleIcon className="h-5 w-5 text-yellow-500" />;
      case 'error':
        return <XMarkIcon className="h-5 w-5 text-red-500" />;
      case 'success':
        return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
      case 'info':
      default:
        return <InformationCircleIcon className="h-5 w-5 text-blue-500" />;
    }
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'urgent':
        return 'border-l-red-500 bg-red-50';
      case 'high':
        return 'border-l-orange-500 bg-orange-50';
      case 'medium':
        return 'border-l-blue-500 bg-blue-50';
      case 'low':
      default:
        return 'border-l-gray-500 bg-gray-50';
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60);
    
    if (diffInHours < 1) {
      return 'Vừa xong';
    } else if (diffInHours < 24) {
      return `${Math.floor(diffInHours)} giờ trước`;
    } else {
      return date.toLocaleDateString('vi-VN');
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      <div className="absolute inset-0 bg-black bg-opacity-50" onClick={onClose} />
      
      <div className="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl transform transition-transform">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b">
          <div className="flex items-center space-x-2">
            <BellIcon className="h-6 w-6 text-gray-600" />
            <h2 className="text-lg font-semibold">Thông báo</h2>
            {unreadCount > 0 && (
              <span className="bg-red-500 text-white text-xs rounded-full px-2 py-1">
                {unreadCount}
              </span>
            )}
          </div>
          <button onClick={onClose} className="p-1 hover:bg-gray-100 rounded">
            <XMarkIcon className="h-5 w-5" />
          </button>
        </div>

        {/* Filters */}
        <div className="p-4 border-b bg-gray-50">
          <div className="flex space-x-2 mb-3">
            <button
              onClick={() => setFilter('all')}
              className={`px-3 py-1 rounded text-sm ${
                filter === 'all' ? 'bg-blue-500 text-white' : 'bg-white text-gray-600'
              }`}
            >
              Tất cả
            </button>
            <button
              onClick={() => setFilter('unread')}
              className={`px-3 py-1 rounded text-sm ${
                filter === 'unread' ? 'bg-blue-500 text-white' : 'bg-white text-gray-600'
              }`}
            >
              Chưa đọc
            </button>
            <button
              onClick={() => setFilter('read')}
              className={`px-3 py-1 rounded text-sm ${
                filter === 'read' ? 'bg-blue-500 text-white' : 'bg-white text-gray-600'
              }`}
            >
              Đã đọc
            </button>
          </div>

          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value)}
            className="w-full px-3 py-1 border border-gray-300 rounded text-sm"
          >
            <option value="all">Tất cả loại</option>
            <option value="warranty_claim">Bảo hành</option>
            <option value="appointment">Lịch hẹn</option>
            <option value="maintenance">Bảo dưỡng</option>
            <option value="campaign">Khuyến mãi</option>
            <option value="info">Thông tin</option>
          </select>

          {unreadCount > 0 && (
            <button
              onClick={markAllAsRead}
              className="mt-2 text-sm text-blue-600 hover:text-blue-800"
            >
              Đánh dấu tất cả đã đọc
            </button>
          )}
        </div>

        {/* Notifications List */}
        <div className="flex-1 overflow-y-auto">
          {loading ? (
            <div className="flex justify-center items-center h-32">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
          ) : notifications.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-32 text-gray-500">
              <BellIcon className="h-12 w-12 mb-2" />
              <p>Không có thông báo nào</p>
            </div>
          ) : (
            <div className="divide-y divide-gray-200">
              {notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`p-4 cursor-pointer hover:bg-gray-50 border-l-4 ${
                    getPriorityColor(notification.priority)
                  } ${!notification.read_at ? 'bg-blue-50' : ''}`}
                  onClick={() => !notification.read_at && markAsRead(notification.id)}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex items-start space-x-3 flex-1">
                      {getTypeIcon(notification.type)}
                      <div className="flex-1 min-w-0">
                        <p className={`text-sm font-medium text-gray-900 ${
                          !notification.read_at ? 'font-semibold' : ''
                        }`}>
                          {notification.title}
                        </p>
                        <p className="text-sm text-gray-600 mt-1">
                          {notification.message}
                        </p>
                        <div className="flex items-center justify-between mt-2">
                          <span className="text-xs text-gray-500">
                            {formatDate(notification.created_at)}
                          </span>
                          <span className={`text-xs px-2 py-1 rounded ${
                            notification.priority === 'urgent' ? 'bg-red-100 text-red-800' :
                            notification.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                            notification.priority === 'medium' ? 'bg-blue-100 text-blue-800' :
                            'bg-gray-100 text-gray-800'
                          }`}>
                            {notification.priority === 'urgent' ? 'Khẩn cấp' :
                             notification.priority === 'high' ? 'Cao' :
                             notification.priority === 'medium' ? 'Trung bình' : 'Thấp'}
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    {!notification.read_at && (
                      <div className="flex-shrink-0 ml-2">
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            markAsRead(notification.id);
                          }}
                          className="p-1 hover:bg-blue-100 rounded text-blue-600"
                          title="Đánh dấu đã đọc"
                        >
                          <CheckIcon className="h-4 w-4" />
                        </button>
                      </div>
                    )}
                  </div>

                  {/* Additional data display */}
                  {notification.data && (
                    <div className="mt-2 text-xs text-gray-500 bg-gray-100 p-2 rounded">
                      {notification.type === 'appointment' && notification.data.appointment_date && (
                        <p>Ngày hẹn: {notification.data.appointment_date}</p>
                      )}
                      {notification.type === 'warranty_claim' && notification.data.claim_number && (
                        <p>Số claim: {notification.data.claim_number}</p>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="p-4 border-t bg-gray-50">
          <button
            onClick={fetchNotifications}
            className="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition-colors"
          >
            Làm mới
          </button>
        </div>
      </div>
    </div>
  );
}