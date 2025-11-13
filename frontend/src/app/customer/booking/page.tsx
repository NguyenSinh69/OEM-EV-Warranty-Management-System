'use client';

import { useState, useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { api } from '@/lib/api';
import type { Vehicle } from '@/types';

export default function BookAppointment() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const vinFromUrl = searchParams.get('vin');
  const claimFromUrl = searchParams.get('claim');

  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [selectedVehicle, setSelectedVehicle] = useState<Vehicle | null>(null);
  const [loading, setLoading] = useState(false);
  const [availableSlots, setAvailableSlots] = useState<string[]>([]);
  const [form, setForm] = useState({
    vin: vinFromUrl || '',
    appointment_date: '',
    appointment_time: '',
    service_type: claimFromUrl ? 'warranty_claim' : '',
    claim_id: claimFromUrl || '',
    notes: '',
  });

  const serviceTypes = [
    { value: 'warranty_claim', label: 'Warranty Claim Service' },
    { value: 'maintenance', label: 'Regular Maintenance' },
    { value: 'inspection', label: 'Vehicle Inspection' },
    { value: 'battery_check', label: 'Battery Health Check' },
    { value: 'software_update', label: 'Software Update' },
    { value: 'other', label: 'Other Service' },
  ];

  const timeSlots = [
    '08:00', '09:00', '10:00', '11:00',
    '13:00', '14:00', '15:00', '16:00', '17:00'
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
    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const minDate = tomorrow.toISOString().split('T')[0];
    setForm(prev => ({ ...prev, appointment_date: minDate }));
  }, []);

  useEffect(() => {
    if (form.appointment_date) {
      // Simulate checking available slots
      // In real app, fetch from backend
      setAvailableSlots(timeSlots);
    }
  }, [form.appointment_date]);

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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedVehicle) {
      alert('Please select a valid vehicle');
      return;
    }

    try {
      setLoading(true);

      const appointmentData = {
        vin: form.vin,
        appointment_date: form.appointment_date,
        appointment_time: form.appointment_time,
        service_type: form.service_type,
        claim_id: form.claim_id ? parseInt(form.claim_id) : null,
        notes: form.notes,
      };

      const response = await api.customer.bookAppointment(appointmentData);

      if (response.success) {
        alert('Appointment booked successfully!');
        router.push('/customer');
      } else {
        alert(`Failed to book appointment: ${response.message}`);
      }
    } catch (error: any) {
      console.error('Error booking appointment:', error);
      alert(error.message || 'Failed to book appointment');
    } finally {
      setLoading(false);
    }
  };

  const getMinDate = () => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
  };

  return (
    <div className="container mx-auto px-4 py-8 max-w-3xl">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">Book Service Appointment</h1>
        <p className="text-gray-600 mt-2">Schedule a visit to our service center</p>
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

        {/* Vehicle Info */}
        {selectedVehicle && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 className="font-semibold text-blue-900 mb-2">Vehicle Details</h3>
            <div className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span className="text-blue-700">Make/Model:</span>
                <span className="ml-2 font-medium">{selectedVehicle.make} {selectedVehicle.model}</span>
              </div>
              <div>
                <span className="text-blue-700">Year:</span>
                <span className="ml-2 font-medium">{selectedVehicle.year}</span>
              </div>
            </div>
          </div>
        )}

        {/* Service Type */}
        <div>
          <label className="block text-gray-700 font-medium mb-2">
            Service Type <span className="text-red-500">*</span>
          </label>
          <select
            value={form.service_type}
            onChange={(e) => setForm({ ...form, service_type: e.target.value })}
            required
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">-- Select service type --</option>
            {serviceTypes.map((type) => (
              <option key={type.value} value={type.value}>
                {type.label}
              </option>
            ))}
          </select>
        </div>

        {/* Date and Time */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-gray-700 font-medium mb-2">
              Appointment Date <span className="text-red-500">*</span>
            </label>
            <input
              type="date"
              value={form.appointment_date}
              onChange={(e) => setForm({ ...form, appointment_date: e.target.value })}
              min={getMinDate()}
              required
              className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div>
            <label className="block text-gray-700 font-medium mb-2">
              Preferred Time <span className="text-red-500">*</span>
            </label>
            <select
              value={form.appointment_time}
              onChange={(e) => setForm({ ...form, appointment_time: e.target.value })}
              required
              disabled={!form.appointment_date}
              className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
            >
              <option value="">-- Select time --</option>
              {availableSlots.map((time) => (
                <option key={time} value={time}>
                  {time}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Additional Notes */}
        <div>
          <label className="block text-gray-700 font-medium mb-2">
            Additional Notes (Optional)
          </label>
          <textarea
            value={form.notes}
            onChange={(e) => setForm({ ...form, notes: e.target.value })}
            rows={4}
            placeholder="Any specific concerns or requests..."
            className="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        {/* Info Box */}
        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div className="flex items-start gap-3">
            <svg className="w-6 h-6 text-blue-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div className="text-sm text-gray-700">
              <p className="font-medium mb-1">Service Center Information</p>
              <p>Address: 123 Service Road, Tech District</p>
              <p>Phone: (555) 123-4567</p>
              <p className="mt-2">Please arrive 10 minutes before your scheduled time.</p>
            </div>
          </div>
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
            disabled={loading}
            className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white py-3 px-6 rounded-lg font-semibold transition-colors"
          >
            {loading ? 'Booking...' : 'Confirm Appointment'}
          </button>
        </div>
      </form>
    </div>
  );
}
