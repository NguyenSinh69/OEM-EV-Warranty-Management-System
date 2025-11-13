'use client';

type StatusType = 
  | 'pending' 
  | 'approved' 
  | 'rejected' 
  | 'in_progress' 
  | 'completed' 
  | 'active' 
  | 'inactive'
  | 'recalled'
  | 'low'
  | 'medium'
  | 'high'
  | 'critical';

interface StatusBadgeProps {
  status: StatusType | string;
  size?: 'sm' | 'md' | 'lg';
}

export default function StatusBadge({ status, size = 'md' }: StatusBadgeProps) {
  const statusConfig: Record<string, { bg: string; text: string; label: string }> = {
    // Claim/Request Status
    pending: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending' },
    approved: { bg: 'bg-green-100', text: 'text-green-800', label: 'Approved' },
    rejected: { bg: 'bg-red-100', text: 'text-red-800', label: 'Rejected' },
    in_progress: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'In Progress' },
    completed: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Completed' },
    
    // Vehicle Status
    active: { bg: 'bg-green-100', text: 'text-green-800', label: 'Active' },
    inactive: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Inactive' },
    recalled: { bg: 'bg-red-100', text: 'text-red-800', label: 'Recalled' },
    
    // Priority
    low: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Low' },
    medium: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Medium' },
    high: { bg: 'bg-orange-100', text: 'text-orange-800', label: 'High' },
    critical: { bg: 'bg-red-100', text: 'text-red-800', label: 'Critical' },
  };

  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-3 py-1 text-sm',
    lg: 'px-4 py-2 text-base',
  };

  const normalizedStatus = status.toLowerCase().replace(/\s+/g, '_');
  const config = statusConfig[normalizedStatus] || statusConfig.pending;

  return (
    <span
      className={`inline-flex items-center rounded-full font-semibold ${config.bg} ${config.text} ${sizeClasses[size]}`}
    >
      {config.label}
    </span>
  );
}
