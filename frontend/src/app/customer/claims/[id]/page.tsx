'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { api } from '@/lib/api';
import type { WarrantyClaim } from '@/types';

export default function ClaimDetails() {
  const params = useParams();
  const router = useRouter();
  const claimId = Number(params.id);

  const [claim, setClaim] = useState<WarrantyClaim | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (claimId) {
      loadClaim();
    }
  }, [claimId]);

  const loadClaim = async () => {
    try {
      setLoading(true);
      const response = await api.customer.getClaimDetails(claimId);
      if (response.success && response.data) {
        setClaim(response.data);
      } else {
        alert('Claim not found');
        router.push('/customer/claims');
      }
    } catch (error) {
      console.error('Failed to load claim:', error);
      alert('Failed to load claim details');
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const styles: Record<string, { bg: string; text: string; label: string }> = {
      pending: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Pending Review' },
      approved: { bg: 'bg-green-100', text: 'text-green-800', label: 'Approved' },
      rejected: { bg: 'bg-red-100', text: 'text-red-800', label: 'Rejected' },
      in_progress: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'In Progress' },
      completed: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Completed' },
    };
    const style = styles[status] || styles.pending;
    return (
      <span className={`px-4 py-2 rounded-full text-sm font-semibold ${style.bg} ${style.text}`}>
        {style.label}
      </span>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (!claim) {
    return (
      <div className="container mx-auto px-4 py-8">
        <div className="text-center">
          <p className="text-gray-600">Claim not found</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8 max-w-4xl">
      {/* Header */}
      <div className="mb-6">
        <button
          onClick={() => router.back()}
          className="text-blue-600 hover:text-blue-700 font-medium mb-4 flex items-center gap-2"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
          Back to Claims
        </button>
        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Claim #{claim.id}</h1>
            <p className="text-gray-600 mt-2">VIN: {claim.vin}</p>
          </div>
          {getStatusBadge(claim.status)}
        </div>
      </div>

      {/* Main Content */}
      <div className="space-y-6">
        {/* Claim Information */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Claim Information</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">Component</p>
              <p className="font-medium text-gray-900">{claim.component || 'N/A'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Failure Date</p>
              <p className="font-medium text-gray-900">
                {claim.failure_date ? new Date(claim.failure_date).toLocaleDateString() : 'N/A'}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Mileage</p>
              <p className="font-medium text-gray-900">{claim.mileage ? `${claim.mileage} km` : 'N/A'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Submitted Date</p>
              <p className="font-medium text-gray-900">
                {claim.created_at ? new Date(claim.created_at).toLocaleDateString() : 'N/A'}
              </p>
            </div>
          </div>
        </div>

        {/* Description */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Issue Description</h2>
          <p className="text-gray-800 whitespace-pre-wrap">
            {claim.failure_description || 'No description provided'}
          </p>
        </div>

        {/* Images */}
        {claim.images && claim.images.length > 0 && (
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-xl font-bold text-gray-900 mb-4">Attached Images</h2>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
              {claim.images.map((image: string, index: number) => (
                <img
                  key={index}
                  src={image}
                  alt={`Claim image ${index + 1}`}
                  className="w-full h-48 object-cover rounded-lg border border-gray-200"
                />
              ))}
            </div>
          </div>
        )}

        {/* Status Updates */}
        {claim.status_notes && (
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-xl font-bold text-gray-900 mb-4">Status Updates</h2>
            <div className="border-l-4 border-blue-500 pl-4 py-2">
              <p className="text-gray-800">{claim.status_notes}</p>
              {claim.updated_at && (
                <p className="text-sm text-gray-500 mt-2">
                  Updated: {new Date(claim.updated_at).toLocaleString()}
                </p>
              )}
            </div>
          </div>
        )}

        {/* Actions */}
        {claim.status === 'pending' && (
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <svg className="w-12 h-12 mx-auto text-yellow-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p className="text-gray-700 font-medium">Your claim is under review</p>
            <p className="text-gray-600 text-sm mt-1">
              Our service center will review your claim and contact you soon
            </p>
          </div>
        )}

        {claim.status === 'approved' && (
          <div className="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
            <svg className="w-12 h-12 mx-auto text-green-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p className="text-gray-700 font-medium mb-3">Your claim has been approved!</p>
            <button
              onClick={() => router.push(`/customer/booking?claim=${claim.id}`)}
              className="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
            >
              Book a Service Appointment
            </button>
          </div>
        )}

        {claim.status === 'rejected' && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-6">
            <div className="flex items-start gap-3">
              <svg className="w-6 h-6 text-red-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
              <div>
                <p className="text-gray-700 font-medium">Claim Rejected</p>
                <p className="text-gray-600 text-sm mt-1">
                  {claim.rejection_reason || 'Please contact the service center for more information'}
                </p>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
