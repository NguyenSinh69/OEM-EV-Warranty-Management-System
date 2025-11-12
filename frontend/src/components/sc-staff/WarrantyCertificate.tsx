'use client';

import { useRef } from 'react';

interface WarrantyCertificateProps {
  vehicle: {
    vin: string;
    model: string;
    make?: string;
    year: number;
    color: string;
    battery_capacity: string;
    purchase_date: string;
    warranty_start_date: string;
    warranty_end_date: string;
  };
  customer: {
    name: string;
    phone: string;
    address: string;
    id_number: string;
  };
}

export default function WarrantyCertificate({ vehicle, customer }: WarrantyCertificateProps) {
  const certificateRef = useRef<HTMLDivElement>(null);

  const handlePrint = () => {
    const printContents = certificateRef.current?.innerHTML;
    if (!printContents) return;

    const originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload(); // Reload to restore React state
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  return (
    <div>
      {/* Print Button */}
      <div className="mb-4 flex justify-end print:hidden">
        <button
          onClick={handlePrint}
          className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center gap-2"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          Print Certificate
        </button>
      </div>

      {/* Certificate */}
      <div
        ref={certificateRef}
        className="bg-white border-4 border-blue-600 p-8 max-w-4xl mx-auto"
        style={{ fontFamily: 'Times New Roman, serif' }}
      >
        {/* Header */}
        <div className="text-center border-b-2 border-blue-600 pb-6 mb-6">
          <div className="flex items-center justify-center gap-4 mb-4">
            <div className="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
              <svg className="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
          </div>
          <h1 className="text-4xl font-bold text-blue-900 mb-2">WARRANTY CERTIFICATE</h1>
          <h2 className="text-2xl text-blue-700">Electric Vehicle Warranty</h2>
          <p className="text-gray-600 mt-2">VinFast OEM EV Warranty Management System</p>
        </div>

        {/* Certificate Number */}
        <div className="text-center mb-6">
          <p className="text-sm text-gray-600">Certificate No.</p>
          <p className="text-xl font-bold text-gray-900 font-mono">{vehicle.vin}</p>
        </div>

        {/* Customer Information */}
        <div className="mb-6">
          <h3 className="text-lg font-bold text-blue-900 mb-3 border-b border-gray-300 pb-2">
            CUSTOMER INFORMATION
          </h3>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">Full Name</p>
              <p className="font-semibold text-gray-900">{customer.name}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">ID Number</p>
              <p className="font-semibold text-gray-900">{customer.id_number}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Phone Number</p>
              <p className="font-semibold text-gray-900">{customer.phone}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Address</p>
              <p className="font-semibold text-gray-900">{customer.address}</p>
            </div>
          </div>
        </div>

        {/* Vehicle Information */}
        <div className="mb-6">
          <h3 className="text-lg font-bold text-blue-900 mb-3 border-b border-gray-300 pb-2">
            VEHICLE INFORMATION
          </h3>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">Make & Model</p>
              <p className="font-semibold text-gray-900">
                {vehicle.make || 'VinFast'} {vehicle.model}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Year</p>
              <p className="font-semibold text-gray-900">{vehicle.year}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">VIN</p>
              <p className="font-semibold text-gray-900 font-mono">{vehicle.vin}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Color</p>
              <p className="font-semibold text-gray-900">{vehicle.color}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Battery Capacity</p>
              <p className="font-semibold text-gray-900">{vehicle.battery_capacity}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Purchase Date</p>
              <p className="font-semibold text-gray-900">{formatDate(vehicle.purchase_date)}</p>
            </div>
          </div>
        </div>

        {/* Warranty Coverage */}
        <div className="mb-6">
          <h3 className="text-lg font-bold text-blue-900 mb-3 border-b border-gray-300 pb-2">
            WARRANTY COVERAGE
          </h3>
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <div className="grid grid-cols-2 gap-4 mb-4">
              <div>
                <p className="text-sm text-gray-600">Warranty Start Date</p>
                <p className="font-bold text-blue-900 text-lg">
                  {formatDate(vehicle.warranty_start_date)}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Warranty End Date</p>
                <p className="font-bold text-blue-900 text-lg">
                  {formatDate(vehicle.warranty_end_date)}
                </p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4 text-sm">
            <div className="flex items-start gap-2">
              <svg className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p className="font-semibold text-gray-900">Battery Pack</p>
                <p className="text-gray-600">8 years / 160,000 km</p>
              </div>
            </div>
            <div className="flex items-start gap-2">
              <svg className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p className="font-semibold text-gray-900">Electric Motor</p>
                <p className="text-gray-600">8 years / 160,000 km</p>
              </div>
            </div>
            <div className="flex items-start gap-2">
              <svg className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p className="font-semibold text-gray-900">Inverter & Charger</p>
                <p className="text-gray-600">5 years / 100,000 km</p>
              </div>
            </div>
            <div className="flex items-start gap-2">
              <svg className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p className="font-semibold text-gray-900">Vehicle Systems</p>
                <p className="text-gray-600">3 years / 100,000 km</p>
              </div>
            </div>
          </div>
        </div>

        {/* Terms & Conditions */}
        <div className="mb-6 text-xs text-gray-700">
          <h4 className="font-bold text-gray-900 mb-2">TERMS & CONDITIONS:</h4>
          <ul className="list-disc list-inside space-y-1">
            <li>This warranty is valid only with proper maintenance records</li>
            <li>Warranty coverage is subject to manufacturer's terms and conditions</li>
            <li>Unauthorized modifications will void the warranty</li>
            <li>Valid only at authorized service centers</li>
          </ul>
        </div>

        {/* Footer */}
        <div className="border-t-2 border-gray-300 pt-6 mt-8">
          <div className="flex justify-between items-end">
            <div className="text-center">
              <p className="text-sm text-gray-600 mb-8">Service Center Manager</p>
              <div className="border-t border-gray-400 w-48"></div>
              <p className="text-xs text-gray-500 mt-1">Signature & Stamp</p>
            </div>
            <div className="text-right">
              <p className="text-sm text-gray-600">Issue Date</p>
              <p className="font-bold text-gray-900">{formatDate(new Date().toISOString())}</p>
            </div>
          </div>
        </div>

        {/* QR Code Placeholder */}
        <div className="text-center mt-6 pt-6 border-t border-gray-200">
          <div className="inline-block bg-gray-100 p-4 rounded-lg">
            <svg className="w-24 h-24 text-gray-400 mx-auto" fill="currentColor" viewBox="0 0 24 24">
              <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z" />
            </svg>
          </div>
          <p className="text-xs text-gray-500 mt-2">Scan to verify certificate authenticity</p>
        </div>
      </div>
    </div>
  );
}
