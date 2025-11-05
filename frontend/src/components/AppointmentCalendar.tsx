import React, { useState, useEffect } from 'react';
import { 
  ChevronLeftIcon, 
  ChevronRightIcon,
  CalendarDaysIcon,
  ClockIcon,
  UserIcon
} from '@heroicons/react/24/outline';

interface Appointment {
  id: number;
  customer_id: number;
  vehicle_vin: string;
  service_center_id: number;
  title: string;
  description?: string;
  type: 'maintenance' | 'repair' | 'warranty' | 'inspection' | 'consultation';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  appointment_date: string;
  start_time: string;
  end_time: string;
  status: 'scheduled' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled';
  customer_name?: string;
  vehicle_model?: string;
  service_center_name?: string;
  technician_name?: string;
}

interface AppointmentCalendarProps {
  serviceCenterId?: number;
  technicianId?: number;
  onAppointmentClick?: (appointment: Appointment) => void;
}

export default function AppointmentCalendar({ 
  serviceCenterId, 
  technicianId, 
  onAppointmentClick 
}: AppointmentCalendarProps) {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [appointments, setAppointments] = useState<{ [key: string]: Appointment[] }>({});
  const [stats, setStats] = useState<any>({});
  const [loading, setLoading] = useState(false);
  const [view, setView] = useState<'month' | 'week' | 'day'>('month');

  useEffect(() => {
    fetchAppointments();
  }, [currentDate, serviceCenterId, technicianId, view]);

  const fetchAppointments = async () => {
    try {
      setLoading(true);
      
      const startDate = getStartDate();
      const endDate = getEndDate();
      
      const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
        ...(serviceCenterId && { service_center_id: serviceCenterId.toString() }),
        ...(technicianId && { technician_id: technicianId.toString() })
      });

      const response = await fetch(`http://localhost:8005/api/appointments/calendar?${params}`);
      const data = await response.json();

      if (data.success) {
        setAppointments(data.data.calendar);
        setStats(data.data.stats);
      }
    } catch (error) {
      console.error('Failed to fetch appointments:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStartDate = () => {
    const date = new Date(currentDate);
    switch (view) {
      case 'month':
        return new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
      case 'week':
        const startOfWeek = new Date(date);
        startOfWeek.setDate(date.getDate() - date.getDay());
        return startOfWeek.toISOString().split('T')[0];
      case 'day':
        return date.toISOString().split('T')[0];
      default:
        return date.toISOString().split('T')[0];
    }
  };

  const getEndDate = () => {
    const date = new Date(currentDate);
    switch (view) {
      case 'month':
        return new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
      case 'week':
        const endOfWeek = new Date(date);
        endOfWeek.setDate(date.getDate() - date.getDay() + 6);
        return endOfWeek.toISOString().split('T')[0];
      case 'day':
        return date.toISOString().split('T')[0];
      default:
        return date.toISOString().split('T')[0];
    }
  };

  const navigateDate = (direction: 'prev' | 'next') => {
    const newDate = new Date(currentDate);
    
    switch (view) {
      case 'month':
        newDate.setMonth(newDate.getMonth() + (direction === 'next' ? 1 : -1));
        break;
      case 'week':
        newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));
        break;
      case 'day':
        newDate.setDate(newDate.getDate() + (direction === 'next' ? 1 : -1));
        break;
    }
    
    setCurrentDate(newDate);
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'scheduled':
        return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'confirmed':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'in_progress':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'completed':
        return 'bg-gray-100 text-gray-800 border-gray-200';
      case 'cancelled':
        return 'bg-red-100 text-red-800 border-red-200';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'maintenance':
        return '🔧';
      case 'repair':
        return '🛠️';
      case 'warranty':
        return '📋';
      case 'inspection':
        return '🔍';
      case 'consultation':
        return '💬';
      default:
        return '📅';
    }
  };

  const renderMonthView = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const days = [];
    const current = new Date(startDate);
    
    while (current <= lastDay || current.getDay() !== 0) {
      days.push(new Date(current));
      current.setDate(current.getDate() + 1);
    }

    return (
      <div className="grid grid-cols-7 gap-1">
        {/* Day headers */}
        {['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'].map(day => (
          <div key={day} className="p-2 text-center text-sm font-medium text-gray-500 bg-gray-50">
            {day}
          </div>
        ))}
        
        {/* Calendar days */}
        {days.map((day, index) => {
          const dateKey = day.toISOString().split('T')[0];
          const dayAppointments = appointments[dateKey] || [];
          const isCurrentMonth = day.getMonth() === month;
          const isToday = day.toDateString() === new Date().toDateString();
          
          return (
            <div
              key={index}
              className={`min-h-[100px] p-1 border border-gray-200 ${
                isCurrentMonth ? 'bg-white' : 'bg-gray-50'
              } ${isToday ? 'ring-2 ring-blue-500' : ''}`}
            >
              <div className={`text-sm font-medium ${
                isCurrentMonth ? 'text-gray-900' : 'text-gray-400'
              } ${isToday ? 'text-blue-600' : ''}`}>
                {day.getDate()}
              </div>
              
              <div className="mt-1 space-y-1">
                {dayAppointments.slice(0, 3).map((appointment) => (
                  <div
                    key={appointment.id}
                    onClick={() => onAppointmentClick?.(appointment)}
                    className={`text-xs p-1 rounded border cursor-pointer hover:shadow-sm ${
                      getStatusColor(appointment.status)
                    }`}
                  >
                    <div className="flex items-center space-x-1">
                      <span>{getTypeIcon(appointment.type)}</span>
                      <span className="truncate">{appointment.title}</span>
                    </div>
                    <div className="flex items-center space-x-1 mt-1">
                      <ClockIcon className="h-3 w-3" />
                      <span>{appointment.start_time.slice(0, 5)}</span>
                    </div>
                  </div>
                ))}
                
                {dayAppointments.length > 3 && (
                  <div className="text-xs text-gray-500 text-center">
                    +{dayAppointments.length - 3} khác
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    );
  };

  const renderWeekView = () => {
    const startOfWeek = new Date(currentDate);
    startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());
    
    const days: Date[] = [];
    for (let i = 0; i < 7; i++) {
      const day = new Date(startOfWeek);
      day.setDate(startOfWeek.getDate() + i);
      days.push(day);
    }

    return (
      <div className="grid grid-cols-8 gap-1">
        {/* Time column header */}
        <div className="p-2 text-center text-sm font-medium text-gray-500 bg-gray-50">
          Giờ
        </div>
        
        {/* Day headers */}
        {days.map(day => (
          <div key={day.toISOString()} className="p-2 text-center text-sm font-medium text-gray-500 bg-gray-50">
            <div>{day.toLocaleDateString('vi-VN', { weekday: 'short' })}</div>
            <div className="text-lg">{day.getDate()}</div>
          </div>
        ))}
        
        {/* Time slots */}
        {Array.from({ length: 10 }, (_, hour) => hour + 8).map(hour => (
          <React.Fragment key={hour}>
            <div className="p-2 text-xs text-gray-500 border-r bg-gray-50">
              {hour}:00
            </div>
            {days.map(day => {
              const dateKey = day.toISOString().split('T')[0];
              const dayAppointments = appointments[dateKey] || [];
              const hourAppointments = dayAppointments.filter(apt => {
                const startHour = parseInt(apt.start_time.split(':')[0]);
                return startHour === hour;
              });
              
              return (
                <div key={`${day.toISOString()}-${hour}`} className="min-h-[60px] p-1 border border-gray-200">
                  {hourAppointments.map(appointment => (
                    <div
                      key={appointment.id}
                      onClick={() => onAppointmentClick?.(appointment)}
                      className={`text-xs p-2 rounded border mb-1 cursor-pointer hover:shadow-sm ${
                        getStatusColor(appointment.status)
                      }`}
                    >
                      <div className="font-medium">{appointment.title}</div>
                      <div className="flex items-center space-x-1 mt-1">
                        <ClockIcon className="h-3 w-3" />
                        <span>{appointment.start_time.slice(0, 5)} - {appointment.end_time.slice(0, 5)}</span>
                      </div>
                      {appointment.customer_name && (
                        <div className="flex items-center space-x-1 mt-1">
                          <UserIcon className="h-3 w-3" />
                          <span className="truncate">{appointment.customer_name}</span>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              );
            })}
          </React.Fragment>
        ))}
      </div>
    );
  };

  const formatDateRange = () => {
    switch (view) {
      case 'month':
        return currentDate.toLocaleDateString('vi-VN', { year: 'numeric', month: 'long' });
      case 'week':
        const startOfWeek = new Date(currentDate);
        startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        return `${startOfWeek.toLocaleDateString('vi-VN')} - ${endOfWeek.toLocaleDateString('vi-VN')}`;
      case 'day':
        return currentDate.toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
      default:
        return '';
    }
  };

  return (
    <div className="bg-white rounded-lg shadow">
      {/* Header */}
      <div className="flex items-center justify-between p-4 border-b">
        <div className="flex items-center space-x-4">
          <h2 className="text-lg font-semibold flex items-center">
            <CalendarDaysIcon className="h-5 w-5 mr-2" />
            Lịch hẹn
          </h2>
          
          {/* View selector */}
          <div className="flex space-x-1 bg-gray-100 rounded-lg p-1">
            {(['month', 'week', 'day'] as const).map((viewType) => (
              <button
                key={viewType}
                onClick={() => setView(viewType)}
                className={`px-3 py-1 rounded text-sm font-medium ${
                  view === viewType
                    ? 'bg-white text-blue-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                {viewType === 'month' ? 'Tháng' : viewType === 'week' ? 'Tuần' : 'Ngày'}
              </button>
            ))}
          </div>
        </div>

        <div className="flex items-center space-x-4">
          {/* Stats */}
          {stats && (
            <div className="flex space-x-4 text-sm">
              <span className="text-blue-600">
                Đã đặt: {stats.scheduled || 0}
              </span>
              <span className="text-green-600">
                Xác nhận: {stats.confirmed || 0}
              </span>
              <span className="text-yellow-600">
                Đang thực hiện: {stats.in_progress || 0}
              </span>
            </div>
          )}

          {/* Navigation */}
          <div className="flex items-center space-x-2">
            <button
              onClick={() => navigateDate('prev')}
              className="p-2 hover:bg-gray-100 rounded"
            >
              <ChevronLeftIcon className="h-5 w-5" />
            </button>
            
            <div className="text-lg font-medium min-w-[200px] text-center">
              {formatDateRange()}
            </div>
            
            <button
              onClick={() => navigateDate('next')}
              className="p-2 hover:bg-gray-100 rounded"
            >
              <ChevronRightIcon className="h-5 w-5" />
            </button>
          </div>

          <button
            onClick={() => setCurrentDate(new Date())}
            className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Hôm nay
          </button>
        </div>
      </div>

      {/* Calendar content */}
      <div className="p-4">
        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>
        ) : (
          <>
            {view === 'month' && renderMonthView()}
            {view === 'week' && renderWeekView()}
            {view === 'day' && (
              <div className="text-center text-gray-500 py-8">
                Day view implementation coming soon
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}