'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';

export default function RoleBasedRouter() {
  const { user, loading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && user) {
      // Redirect based on user role
      switch (user.role) {
        case 'admin':
          router.push('/admin');
          break;
        case 'evm_staff':
          router.push('/evm-staff');
          break;
        case 'sc_staff':
          router.push('/sc-staff');
          break;
        case 'technician':
          router.push('/technician');
          break;
        case 'customer':
          router.push('/customer');
          break;
        default:
          router.push('/login');
      }
    } else if (!loading && !user) {
      router.push('/login');
    }
  }, [user, loading, router]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="text-center">
        <h1 className="text-4xl font-bold text-gray-900 mb-4">
          EVM Warranty Management System
        </h1>
        <p className="text-lg text-gray-600">
          Redirecting to your dashboard...
        </p>
      </div>
    </div>
  );
}