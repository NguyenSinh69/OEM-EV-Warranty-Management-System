'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { api } from '@/lib/api';
import type { WarrantyClaim } from '@/types';

export default function MyClaims() {
  const [claims, setClaims] = useState<WarrantyClaim[]>([]);
  const [filteredClaims, setFilteredClaims] = useState<WarrantyClaim[]>([]);
  const [statusFilter, setStatusFilter] = useState('all');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadClaims();
  }, []);

  useEffect(() => {
    if (statusFilter === 'all') {
      setFilteredClaims(claims);
    } else {
      setFilteredClaims(claims.filter(claim => claim.status === statusFilter));
    }
  }, [statusFilter, claims]);

  const loadClaims = async () => {
    try {
      setLoading(true);
      const response = await api.customer.getMyClaims();
      if (response.success) {
        setClaims(response.data || []);
        setFilteredClaims(response.data || []);
      }
    } catch (error) {
      console.error('Failed to load claims:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const styles: Record<string, string> = {
      pending: 'bg-yellow-100 text-yellow-800',
      approved: 'bg-green-100 text-green-800',
      rejected: 'bg-red-100 text-red-800',
      in_progress: 'bg-blue-100 text-blue-800',
      completed: 'bg-gray-100 text-gray-800',
    };
    return styles[status] || 'bg-gray-100 text-gray-800';
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
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">My Warranty Claims</h1>
          <p className="text-gray-600 mt-2">Track and manage your warranty claims</p>
        </div>
        <Link
          href="/customer/claims/new"
          className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
          </svg>
          New Claim
        </Link>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4 mb-6">
        <div className="flex items-center gap-4">
          <label className="text-gray-700 font-medium">Filter by Status:</label>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="all">All Claims</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
          </select>
          <span className="text-gray-600 ml-auto">
            {filteredClaims.length} claim{filteredClaims.length !== 1 ? 's' : ''}
          </span>
        </div>
      </div>

      {/* Claims List */}
      <div className="space-y-4">
        {filteredClaims.length === 0 ? (
          <div className="bg-white rounded-lg shadow p-12 text-center">
            <svg className="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p className="text-gray-600 text-lg mb-4">No claims found</p>
            <Link
              href="/customer/claims/new"
              className="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors"
            >
              Create Your First Claim
            </Link>
          </div>
        ) : (
          filteredClaims.map((claim) => (
            <div key={claim.id} className="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
              <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="text-xl font-bold text-gray-900">
                        Claim #{claim.id}
                      </h3>
                      <span className={`px-3 py-1 rounded-full text-sm font-semibold ${getStatusBadge(claim.status)}`}>
                        {claim.status.replace('_', ' ').toUpperCase()}
                      </span>
                    </div>
                    <p className="text-gray-600">VIN: {claim.vin}</p>
                  </div>
                  <Link
                    href={`/customer/claims/${claim.id}`}
                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                  >
                    View Details
                  </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <div>
                    <p className="text-sm text-gray-600">Component</p>
                    <p className="font-medium">{claim.component || 'N/A'}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-600">Failure Date</p>
                    <p className="font-medium">
                      {claim.failure_date ? new Date(claim.failure_date).toLocaleDateString() : 'N/A'}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-600">Submitted</p>
                    <p className="font-medium">
                      {claim.created_at ? new Date(claim.created_at).toLocaleDateString() : 'N/A'}
                    </p>
                  </div>
                </div>

                <div>
                  <p className="text-sm text-gray-600 mb-1">Description</p>
                  <p className="text-gray-800 line-clamp-2">{claim.failure_description || 'No description provided'}</p>
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
