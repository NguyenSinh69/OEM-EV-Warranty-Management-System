'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { api } from '@/lib/api';
import type { Vehicle } from '@/types';

export default function CustomerPortal() {
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [stats, setStats] = useState({
    totalVehicles: 0,
    activeClaims: 0,
    upcomingAppointments: 0,
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [vehiclesRes, claimsRes, appointmentsRes] = await Promise.all([
        api.customer.getMyVehicles(),
        api.customer.getMyClaims('pending'),
        api.customer.getMyAppointments(),
      ]);

      if (vehiclesRes.success) {
        setVehicles(vehiclesRes.data || []);
        setStats({
          totalVehicles: vehiclesRes.data?.length || 0,
          activeClaims: claimsRes.data?.length || 0,
          upcomingAppointments: appointmentsRes.data?.filter((a: any) => 
            new Date(a.appointment_date) > new Date()
          ).length || 0,
        });
      }
    } catch (error) {
      console.error('Failed to load data:', error);
    } finally {
      setLoading(false);
    }
  };

  const calculateWarrantyExpiry = (warrantyStartDate: string, warrantyMonths: number) => {
    const startDate = new Date(warrantyStartDate);
    startDate.setMonth(startDate.getMonth() + warrantyMonths);
    return startDate;
  };

  const getWarrantyStatus = (expiryDate: Date) => {
    const now = new Date();
    const daysRemaining = Math.ceil((expiryDate.getTime() - now.getTime()) / (1000 * 3600 * 24));
    
    if (daysRemaining < 0) return { text: 'Expired', color: 'text-red-600' };
    if (daysRemaining < 90) return { text: `${daysRemaining} days left`, color: 'text-orange-500' };
    return { text: 'Active', color: 'text-green-600' };
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">My Vehicles & Warranty</h1>
        <p className="text-gray-600 mt-2">Manage your vehicles, warranty claims, and service appointments</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm">Total Vehicles</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">{stats.totalVehicles}</p>
            </div>
            <div className="bg-blue-100 rounded-full p-3">
              <svg className="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm">Active Claims</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">{stats.activeClaims}</p>
            </div>
            <div className="bg-orange-100 rounded-full p-3">
              <svg className="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-gray-600 text-sm">Upcoming Appointments</p>
              <p className="text-3xl font-bold text-gray-900 mt-2">{stats.upcomingAppointments}</p>
            </div>
            <div className="bg-green-100 rounded-full p-3">
              <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <Link href="/customer/claims/new" className="bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow p-4 text-center transition-colors">
          <svg className="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          <span className="font-semibold">New Claim</span>
        </Link>

        <Link href="/customer/claims" className="bg-white hover:bg-gray-50 border-2 border-gray-200 rounded-lg shadow p-4 text-center transition-colors">
          <svg className="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <span className="font-semibold text-gray-700">View Claims</span>
        </Link>

        <Link href="/customer/booking" className="bg-white hover:bg-gray-50 border-2 border-gray-200 rounded-lg shadow p-4 text-center transition-colors">
          <svg className="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span className="font-semibold text-gray-700">Book Service</span>
        </Link>

        <Link href="/customer/notifications" className="bg-white hover:bg-gray-50 border-2 border-gray-200 rounded-lg shadow p-4 text-center transition-colors">
          <svg className="w-8 h-8 mx-auto mb-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span className="font-semibold text-gray-700">Notifications</span>
        </Link>
      </div>

      {/* Vehicles List */}
      <div className="bg-white rounded-lg shadow">
        <div className="p-6 border-b border-gray-200">
          <h2 className="text-xl font-bold text-gray-900">My Vehicles</h2>
        </div>
        <div className="p-6">
          {vehicles.length === 0 ? (
            <div className="text-center py-12">
              <svg className="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p className="text-gray-600">No vehicles registered</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {vehicles.map((vehicle) => {
                const expiryDate = calculateWarrantyExpiry(
                  vehicle.warranty_start_date,
                  vehicle.warranty_months || 36
                );
                const warrantyStatus = getWarrantyStatus(expiryDate);

                return (
                  <div key={vehicle.id} className="border border-gray-200 rounded-lg p-5 hover:shadow-md transition-shadow">
                    <div className="flex items-start justify-between mb-4">
                      <div>
                        <h3 className="font-bold text-lg text-gray-900">
                          {vehicle.make} {vehicle.model}
                        </h3>
                        <p className="text-sm text-gray-600">{vehicle.year}</p>
                      </div>
                      <span className={`text-sm font-semibold ${warrantyStatus.color}`}>
                        {warrantyStatus.text}
                      </span>
                    </div>

                    <div className="space-y-2 text-sm mb-4">
                      <div className="flex justify-between">
                        <span className="text-gray-600">VIN:</span>
                        <span className="font-mono font-medium">{vehicle.vin}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Battery:</span>
                        <span className="font-medium">{vehicle.battery_capacity || 'N/A'} kWh</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600">Warranty Expires:</span>
                        <span className="font-medium">{expiryDate.toLocaleDateString()}</span>
                      </div>
                    </div>

                    <div className="flex gap-2">
                      <Link 
                        href={`/customer/claims/new?vin=${vehicle.vin}`}
                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded text-center text-sm font-medium transition-colors"
                      >
                        File Claim
                      </Link>
                      <Link 
                        href={`/customer/booking?vin=${vehicle.vin}`}
                        className="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded text-center text-sm font-medium transition-colors"
                      >
                        Book Service
                      </Link>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
