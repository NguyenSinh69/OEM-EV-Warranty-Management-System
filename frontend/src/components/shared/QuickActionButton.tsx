'use client';

import Link from 'next/link';

interface QuickActionButtonProps {
  href: string;
  icon: React.ReactNode;
  label: string;
  variant?: 'primary' | 'secondary';
  badge?: number;
}

export default function QuickActionButton({
  href,
  icon,
  label,
  variant = 'secondary',
  badge,
}: QuickActionButtonProps) {
  const variantClasses = {
    primary: 'bg-blue-600 hover:bg-blue-700 text-white',
    secondary: 'bg-white hover:bg-gray-50 border-2 border-gray-200 text-gray-700',
  };

  return (
    <Link
      href={href}
      className={`rounded-lg shadow p-4 text-center transition-colors relative ${variantClasses[variant]}`}
    >
      {badge !== undefined && badge > 0 && (
        <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
          {badge > 99 ? '99+' : badge}
        </span>
      )}
      
      <div className="flex flex-col items-center gap-2">
        <div className="w-8 h-8">
          {icon}
        </div>
        <span className="font-semibold text-sm">{label}</span>
      </div>
    </Link>
  );
}
