'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function SCStaffPage() {
  const router = useRouter();

  useEffect(() => {
    // Redirect to vehicle registration as default page
    router.replace('/sc-staff/vehicle-registration');
  }, [router]);

  return (
    <div className="p-6">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
        <p className="mt-4 text-gray-600">Loading SC Staff Dashboard...</p>
      </div>
    </div>
  );
}