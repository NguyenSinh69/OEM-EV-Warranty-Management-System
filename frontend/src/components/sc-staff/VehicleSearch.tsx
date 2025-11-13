'use client';

import { useState, useEffect, useRef } from 'react';
import { api } from '@/lib/api';

interface VehicleSearchProps {
  onSelect: (vehicle: any) => void;
  placeholder?: string;
  searchType?: 'all' | 'vin' | 'license_plate';
}

export default function VehicleSearch({
  onSelect,
  placeholder = 'Search by VIN or License Plate...',
  searchType = 'all',
}: VehicleSearchProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [showDropdown, setShowDropdown] = useState(false);
  const wrapperRef = useRef<HTMLDivElement>(null);

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
        setShowDropdown(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Debounced search
  useEffect(() => {
    if (query.length < 3) {
      setResults([]);
      setShowDropdown(false);
      return;
    }

    const timer = setTimeout(() => {
      searchVehicles();
    }, 300);

    return () => clearTimeout(timer);
  }, [query]);

  const searchVehicles = async () => {
    try {
      setLoading(true);
      const response = await api.scStaff.searchVehicles(query, searchType);
      if (response.success && response.data) {
        setResults(response.data);
        setShowDropdown(true);
      }
    } catch (error) {
      console.error('Search failed:', error);
      setResults([]);
    } finally {
      setLoading(false);
    }
  };

  const handleSelect = (vehicle: any) => {
    setQuery(vehicle.vin);
    setShowDropdown(false);
    onSelect(vehicle);
  };

  const highlightMatch = (text: string, query: string) => {
    const parts = text.split(new RegExp(`(${query})`, 'gi'));
    return parts.map((part, index) =>
      part.toLowerCase() === query.toLowerCase() ? (
        <span key={index} className="bg-yellow-200 font-semibold">
          {part}
        </span>
      ) : (
        part
      )
    );
  };

  return (
    <div ref={wrapperRef} className="relative">
      <div className="relative">
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          onFocus={() => results.length > 0 && setShowDropdown(true)}
          placeholder={placeholder}
          className="w-full border border-gray-300 rounded-lg px-4 py-3 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
        <svg
          className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
        {loading && (
          <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
            <svg
              className="animate-spin h-5 w-5 text-blue-600"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              ></circle>
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
          </div>
        )}
      </div>

      {/* Dropdown Results */}
      {showDropdown && results.length > 0 && (
        <div className="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto">
          {results.map((vehicle, index) => (
            <button
              key={index}
              onClick={() => handleSelect(vehicle)}
              className="w-full px-4 py-3 text-left hover:bg-blue-50 border-b border-gray-100 last:border-b-0 transition-colors"
            >
              <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-2">
                  <svg className="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span className="font-semibold text-gray-900">
                    {vehicle.model_full_name || vehicle.model}
                  </span>
                </div>
                <span className="text-sm text-gray-500">{vehicle.year}</span>
              </div>

              <div className="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <span className="text-gray-600">VIN: </span>
                  <span className="font-mono font-medium">
                    {highlightMatch(vehicle.vin, query)}
                  </span>
                </div>
                {vehicle.license_plate && (
                  <div>
                    <span className="text-gray-600">Plate: </span>
                    <span className="font-medium">
                      {highlightMatch(vehicle.license_plate, query)}
                    </span>
                  </div>
                )}
                {vehicle.customer_name && (
                  <div>
                    <span className="text-gray-600">Owner: </span>
                    <span className="font-medium">{vehicle.customer_name}</span>
                  </div>
                )}
                {vehicle.color && (
                  <div>
                    <span className="text-gray-600">Color: </span>
                    <span className="font-medium">{vehicle.color}</span>
                  </div>
                )}
              </div>

              {vehicle.status && (
                <div className="mt-2">
                  <span
                    className={`inline-block text-xs px-2 py-1 rounded ${
                      vehicle.status === 'active'
                        ? 'bg-green-100 text-green-800'
                        : vehicle.status === 'recalled'
                        ? 'bg-red-100 text-red-800'
                        : 'bg-gray-100 text-gray-800'
                    }`}
                  >
                    {vehicle.status.toUpperCase()}
                  </span>
                </div>
              )}
            </button>
          ))}
        </div>
      )}

      {/* No Results */}
      {showDropdown && !loading && query.length >= 3 && results.length === 0 && (
        <div className="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg p-4 text-center">
          <svg className="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p className="text-gray-600 font-medium">No vehicles found</p>
          <p className="text-sm text-gray-500 mt-1">Try a different VIN or license plate</p>
        </div>
      )}
    </div>
  );
}
