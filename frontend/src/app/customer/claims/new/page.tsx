'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { api } from '@/lib/api';
import type { Vehicle } from '@/types';

export default function NewClaim() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const vinFromUrl = searchParams.get('vin');

  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [selectedVehicle, setSelectedVehicle] = useState<Vehicle | null>(null);
  const [loading, setLoading] = useState(false);
  const [uploadingFiles, setUploadingFiles] = useState(false);
  const [form, setForm] = useState({
    vin: vinFromUrl || '',
    component: '',
    failure_description: '',
    failure_date: '',
    mileage: '',
    images: [] as File[],
  });

  const components = [
    'Battery',
    'Motor',
    'Inverter',
    'Charger',
    'BMS (Battery Management System)',
    'Cooling System',
    'Power Electronics',
    'Transmission',
    'Other',
  ];

  useEffect(() => {
    loadVehicles();
  }, []);

  useEffect(() => {
    if (vinFromUrl && vehicles.length > 0) {
      const vehicle = vehicles.find(v => v.vin === vinFromUrl);
      if (vehicle) setSelectedVehicle(vehicle);
    }
  }, [vinFromUrl, vehicles]);

  useEffect(() => {
    // Set default failure date after mount to avoid hydration error
    const today = new Date().toISOString().split('T')[0];
    setForm(prev => ({ ...prev, failure_date: today }));
  }, []);

  const loadVehicles = async () => {
    try {
      const response = await api.customer.getMyVehicles();
      if (response.success) {
        setVehicles(response.data || []);
      }
    } catch (error) {
      console.error('Failed to load vehicles:', error);
    }
  };

  const handleVinChange = (vin: string) => {
    setForm({ ...form, vin });
    const vehicle = vehicles.find(v => v.vin === vin);
    setSelectedVehicle(vehicle || null);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const files = Array.from(e.target.files).slice(0, 5); // Max 5 images
      setForm({ ...form, images: files });
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!selectedVehicle) {
      alert('Please select a valid vehicle');
      return;
    }

    try {
      setLoading(true);

      // Upload images first
      let imageUrls: string[] = [];
      if (form.images.length > 0) {
        setUploadingFiles(true);
        const uploadPromises = form.images.map(file =>
          api.scStaff.uploadFile(file, 'claims')
        );
        const uploadResults = await Promise.all(uploadPromises);
        imageUrls = uploadResults
          .filter(r => r.success)
          .map(r => r.data.url);
        setUploadingFiles(false);
      }

      // Create claim
      const claimData = {
        vin: form.vin,
        component: form.component,
        failure_description: form.failure_description,
        failure_date: form.failure_date,
        mileage: parseInt(form.mileage),
        images: imageUrls,
      };

      const response = await api.customer.createClaim(claimData);

      if (response.success) {
        alert('Warranty claim submitted successfully!');
        router.push('/customer/claims');
      } else {
        alert(`Failed to submit claim: ${response.message}`);
      }
    } catch (error: any) {
      console.error('Error submitting claim:', error);
      alert(error.message || 'Failed to submit claim');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto px-4 py-8 max-w-3xl">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">File a Warranty Claim</h1>
        <p className="text-gray-600 mt-2">Submit details about the issue with your vehicle</p>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
        {/* Vehicle Selection */}
        <div>
          <label className="block text-gray-700 font-medium mb-2">
            Select Vehicle <span className="text-red-500">*</span>
          </label>
          <select
            value={form.vin}
            onChange={(e) => handleVinChange(e.target.value)}
            required
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">-- Choose a vehicle --</option>
            {vehicles.map((vehicle) => (
              <option key={vehicle.id} value={vehicle.vin}>
                {vehicle.year} {vehicle.make} {vehicle.model} ({vehicle.vin})
              </option>
            ))}
          </select>
        </div>

        {/* Vehicle Info Card */}
        {selectedVehicle && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 className="font-semibold text-blue-900 mb-2">Vehicle Information</h3>
            <div className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span className="text-blue-700">Make/Model:</span>
                <span className="ml-2 font-medium">{selectedVehicle.make} {selectedVehicle.model}</span>
              </div>
              <div>
                <span className="text-blue-700">Year:</span>
                <span className="ml-2 font-medium">{selectedVehicle.year}</span>
              </div>
              <div>
                <span className="text-blue-700">Battery:</span>
                <span className="ml-2 font-medium">{selectedVehicle.battery_capacity} kWh</span>
              </div>
              <div>
                <span className="text-blue-700">Warranty Start:</span>
                <span className="ml-2 font-medium">
                  {new Date(selectedVehicle.warranty_start_date).toLocaleDateString()}
                </span>
              </div>
            </div>
          </div>
        )}

        {/* Component */}
        <div>
          <label className="block text-gray-700 font-medium mb-2">
            Failed Component <span className="text-red-500">*</span>
          </label>
          <select
            value={form.component}
            onChange={(e) => setForm({ ...form, component: e.target.value })}
            required
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">-- Select component --</option>
            {components.map((comp) => (
              <option key={comp} value={comp}>{comp}</option>
            ))}
          </select>
        </div>

        {/* Failure Description */}
        <div>
          <label className="block text-gray-700 font-medium mb-2">
            Description of Issue <span className="text-red-500">*</span>
          </label>
          <textarea
            value={form.failure_description}
            onChange={(e) => setForm({ ...form, failure_description: e.target.value })}
            required
            rows={5}
            placeholder="Please describe the problem in detail..."
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p className="text-sm text-gray-500 mt-1">
            Include symptoms, when it started, and any relevant details
          </p>
        </div>

        {/* Failure Date and Mileage */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-gray-700 font-medium mb-2">
              Failure Date <span className="text-red-500">*</span>
            </label>
            <input
              type="date"
              value={form.failure_date}
              onChange={(e) => setForm({ ...form, failure_date: e.target.value })}
              required
              className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div>
            <label className="block text-gray-700 font-medium mb-2">
              Current Mileage (km) <span className="text-red-500">*</span>
            </label>
            <input
              type="number"
              value={form.mileage}
              onChange={(e) => setForm({ ...form, mileage: e.target.value })}
              required
              min="0"
              placeholder="e.g. 15000"
              className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        {/* Image Upload */}
        <div>
          <label className="block text-gray-700 font-medium mb-2">
            Upload Images (Optional)
          </label>
          <input
            type="file"
            onChange={handleFileChange}
            accept="image/*"
            multiple
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p className="text-sm text-gray-500 mt-1">
            Upload up to 5 images showing the issue (Max 5MB each)
          </p>
          {form.images.length > 0 && (
            <div className="mt-3 flex gap-2 flex-wrap">
              {Array.from(form.images).map((file, index) => (
                <div key={index} className="text-sm bg-gray-100 px-3 py-1 rounded">
                  {file.name}
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Submit Buttons */}
        <div className="flex gap-4 pt-4">
          <button
            type="button"
            onClick={() => router.back()}
            className="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 px-6 rounded-lg font-semibold transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={loading || uploadingFiles}
            className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white py-3 px-6 rounded-lg font-semibold transition-colors"
          >
            {uploadingFiles ? 'Uploading Images...' : loading ? 'Submitting...' : 'Submit Claim'}
          </button>
        </div>
      </form>
    </div>
  );
}
