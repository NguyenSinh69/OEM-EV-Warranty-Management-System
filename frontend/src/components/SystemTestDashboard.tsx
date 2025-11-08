'use client';

import { useState, useEffect } from 'react';
import { 
  TruckIcon,
  UserGroupIcon,
  ClipboardDocumentListIcon,
  BellIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/react/24/outline';
import { api } from '@/lib/api';
import { Vehicle, Customer, WarrantyClaim } from '@/types';

interface QuickStatsProps {
  title: string;
  value: number;
  icon: React.ComponentType<any>;
  color: string;
  loading?: boolean;
}

function QuickStats({ title, value, icon: Icon, color, loading }: QuickStatsProps) {
  return (
    <div className="bg-white p-6 rounded-lg shadow border border-gray-200">
      <div className="flex items-center">
        <Icon className={`h-8 w-8 ${color}`} />
        <div className="ml-4">
          <p className="text-sm font-medium text-gray-600">{title}</p>
          <p className="text-2xl font-bold text-gray-900">
            {loading ? '...' : value.toLocaleString()}
          </p>
        </div>
      </div>
    </div>
  );
}

export default function SystemTestDashboard() {
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [claims, setClaims] = useState<WarrantyClaim[]>([]);
  const [servicesHealth, setServicesHealth] = useState<Record<string, any>>({});
  const [loading, setLoading] = useState(true);
  const [testResults, setTestResults] = useState<Record<string, boolean>>({});

  useEffect(() => {
    runSystemTests();
  }, []);

  const runSystemTests = async () => {
    setLoading(true);
    const results: Record<string, boolean> = {};

    try {
      // Test 1: Services Health
      console.log('Testing services health...');
      const health = await api.checkServicesHealth();
      setServicesHealth(health);
      results.servicesHealth = Object.values(health).every(h => h.status === 'healthy');

      // Test 2: Load Vehicles
      console.log('Testing vehicles API...');
      try {
        const vehicleResponse = await api.getVehicles();
        if (vehicleResponse.success && vehicleResponse.data) {
          setVehicles(vehicleResponse.data);
          results.vehicles = true;
        }
      } catch (error) {
        console.error('Vehicle API test failed:', error);
        results.vehicles = false;
      }

      // Test 3: Load Customers
      console.log('Testing customers API...');
      try {
        const customerResponse = await api.getCustomers();
        if (customerResponse.success && customerResponse.data) {
          setCustomers(customerResponse.data);
          results.customers = true;
        }
      } catch (error) {
        console.error('Customer API test failed:', error);
        results.customers = false;
      }

      // Test 4: Load Claims
      console.log('Testing warranty claims API...');
      try {
        const claimResponse = await api.getWarrantyClaims();
        if (claimResponse.success && claimResponse.data) {
          setClaims(claimResponse.data);
          results.claims = true;
        }
      } catch (error) {
        console.error('Claims API test failed:', error);
        results.claims = false;
      }

      // Test 5: Authentication Test
      console.log('Testing authentication...');
      try {
        const loginResponse = await api.login({ 
          email: 'nguyenvana@example.com', 
          password: 'password123' 
        });
        results.authentication = loginResponse.success;
      } catch (error) {
        console.error('Auth test failed:', error);
        results.authentication = false;
      }

      setTestResults(results);
    } catch (error) {
      console.error('System test failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const getTestStatusIcon = (passed: boolean) => {
    return passed ? (
      <CheckCircleIcon className="h-5 w-5 text-green-600" />
    ) : (
      <XCircleIcon className="h-5 w-5 text-red-600" />
    );
  };

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900">EVM System Test Dashboard</h1>
          <p className="text-gray-600 mt-2">
            Comprehensive testing of all microservices and integrations
          </p>
          <button
            onClick={runSystemTests}
            className="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
            disabled={loading}
          >
            {loading ? 'Running Tests...' : 'Run System Tests'}
          </button>
        </div>

        {/* Test Results */}
        <div className="bg-white rounded-lg shadow p-6 mb-8">
          <h2 className="text-xl font-semibold mb-4">Test Results</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div className="flex items-center space-x-2">
              {getTestStatusIcon(testResults.servicesHealth)}
              <span>Services Health</span>
            </div>
            <div className="flex items-center space-x-2">
              {getTestStatusIcon(testResults.vehicles)}
              <span>Vehicle API</span>
            </div>
            <div className="flex items-center space-x-2">
              {getTestStatusIcon(testResults.customers)}
              <span>Customer API</span>
            </div>
            <div className="flex items-center space-x-2">
              {getTestStatusIcon(testResults.claims)}
              <span>Warranty Claims API</span>
            </div>
            <div className="flex items-center space-x-2">
              {getTestStatusIcon(testResults.authentication)}
              <span>Authentication</span>
            </div>
          </div>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <QuickStats
            title="Total Vehicles"
            value={vehicles.length}
            icon={TruckIcon}
            color="text-blue-600"
            loading={loading}
          />
          <QuickStats
            title="Total Customers"
            value={customers.length}
            icon={UserGroupIcon}
            color="text-green-600"
            loading={loading}
          />
          <QuickStats
            title="Warranty Claims"
            value={claims.length}
            icon={ClipboardDocumentListIcon}
            color="text-yellow-600"
            loading={loading}
          />
          <QuickStats
            title="Active Services"
            value={Object.values(servicesHealth).filter(h => h.status === 'healthy').length}
            icon={BellIcon}
            color="text-purple-600"
            loading={loading}
          />
        </div>

        {/* Services Health Grid */}
        <div className="bg-white rounded-lg shadow mb-8">
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-xl font-semibold">Services Health Status</h2>
          </div>
          <div className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              {Object.entries(servicesHealth).map(([service, health]) => (
                <div key={service} className="border rounded-lg p-4">
                  <div className="flex items-center justify-between mb-2">
                    <h3 className="font-medium capitalize">{service.replace('_', ' ')}</h3>
                    <div className={`w-3 h-3 rounded-full ${
                      health.status === 'healthy' ? 'bg-green-500' : 'bg-red-500'
                    }`}></div>
                  </div>
                  <p className="text-sm text-gray-600">
                    Status: {health.status || 'Unknown'}
                  </p>
                  {health.timestamp && (
                    <p className="text-xs text-gray-400 mt-1">
                      {new Date(health.timestamp).toLocaleTimeString()}
                    </p>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Sample Data */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Vehicles */}
          <div className="bg-white rounded-lg shadow">
            <div className="p-6 border-b border-gray-200">
              <h2 className="text-xl font-semibold">Sample Vehicles</h2>
            </div>
            <div className="p-6">
              {loading ? (
                <div className="text-center py-4">Loading vehicles...</div>
              ) : vehicles.length > 0 ? (
                <div className="space-y-4">
                  {vehicles.slice(0, 3).map((vehicle) => (
                    <div key={vehicle.id} className="border rounded-lg p-4">
                      <h3 className="font-medium">{vehicle.model}</h3>
                      <p className="text-sm text-gray-600">VIN: {vehicle.vin}</p>
                      <p className="text-sm text-gray-600">Year: {vehicle.year}</p>
                      <p className="text-sm text-gray-600">Status: {vehicle.status}</p>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-4 text-gray-500">
                  No vehicles found
                </div>
              )}
            </div>
          </div>

          {/* Claims */}
          <div className="bg-white rounded-lg shadow">
            <div className="p-6 border-b border-gray-200">
              <h2 className="text-xl font-semibold">Recent Claims</h2>
            </div>
            <div className="p-6">
              {loading ? (
                <div className="text-center py-4">Loading claims...</div>
              ) : claims.length > 0 ? (
                <div className="space-y-4">
                  {claims.slice(0, 3).map((claim) => (
                    <div key={claim.id} className="border rounded-lg p-4">
                      <h3 className="font-medium">{claim.claim_number}</h3>
                      <p className="text-sm text-gray-600">Type: {claim.issue_type}</p>
                      <p className="text-sm text-gray-600">Priority: {claim.priority}</p>
                      <p className="text-sm text-gray-600">Status: {claim.status}</p>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-4 text-gray-500">
                  No claims found
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}