'use client';

import { useState, useEffect } from 'react';
import { 
  CalendarIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  TruckIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  DocumentTextIcon
} from '@heroicons/react/24/outline';
import { api } from '@/lib/api';

// Type definitions
interface VehicleRegistrationForm {
  vin: string;
  model_id: string;
  year: string;
  color: string;
  customer_id: string;
  purchase_date: string;
  warranty_start_date: string;
}

interface WarrantyClaimForm {
  vehicle_id: string;
  issue_description: string;
  symptoms: string;
  failure_date: string;
  failure_mileage: string;
  priority: 'low' | 'medium' | 'high' | 'critical';
  vehicle_part_id?: string;
}

interface Model {
  id: string;
  name: string;
  full_name: string;
}

interface Customer {
  id: string;
  name: string;
  phone: string;
}

interface ReferenceData {
  models: Model[];
  customers: Customer[];
  parts: any[];
}

interface WarrantyClaim {
  claim_number: string;
  vin: string;
  model_name: string;
  customer_name: string;
  issue_description: string;
  priority: string;
  status: string;
  created_at: string;
}

interface RecallCampaign {
  campaign_number: string;
  title: string;
  campaign_type: string;
  description: string;
  severity: string;
  affected_vehicles: number;
  completed_vehicles: number;
}

interface SearchResult {
  vin: string;
  model_full_name: string;
  year: number;
  color: string;
  license_plate: string;
  customer_name: string;
  customer_phone: string;
  mileage: number;
  status: string;
}

export default function SCStaffDashboardNew() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchType, setSearchType] = useState('all');
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [loading, setLoading] = useState(false);

  // Dashboard stats
  const [stats, setStats] = useState({
    today_registrations: 0,
    pending_claims: 0,
    active_recalls: 0,
    total_vehicles: 0
  });

  // Load dashboard stats from API
  useEffect(() => {
    if (activeTab === 'dashboard') {
      loadDashboardStats();
    }
  }, [activeTab]);

  const loadDashboardStats = async () => {
    try {
      const response = await api.scStaff.getDashboardStats();
      if (response.success) {
        setStats(response.data);
      }
    } catch (error) {
      console.error('Failed to load dashboard stats:', error);
    }
  };

  // Forms state
  const [vehicleForm, setVehicleForm] = useState<VehicleRegistrationForm>({
    vin: '',
    model_id: '',
    year: '',
    color: '',
    customer_id: '',
    purchase_date: '',
    warranty_start_date: ''
  });

  const [claimForm, setClaimForm] = useState<WarrantyClaimForm>({
    vehicle_id: '',
    issue_description: '',
    symptoms: '',
    failure_date: '',
    failure_mileage: '',
    priority: 'medium'
  });

  // Reference data
  const [referenceData, setReferenceData] = useState<ReferenceData>({
    models: [],
    customers: [],
    parts: []
  });

  const [warrantyClaims, setWarrantyClaims] = useState<WarrantyClaim[]>([]);
  const [recallCampaigns, setRecallCampaigns] = useState<RecallCampaign[]>([]);

  // Load dashboard data on mount
  useEffect(() => {
    // Set default dates after mount to avoid hydration mismatch
    const today = new Date().toISOString().split('T')[0];
    const currentYear = new Date().getFullYear().toString();
    
    setVehicleForm(prev => ({
      ...prev,
      year: currentYear,
      purchase_date: today,
      warranty_start_date: today
    }));
    
    setClaimForm(prev => ({
      ...prev,
      failure_date: today
    }));
    
    loadDashboardStats();
    loadReferenceData();
    loadWarrantyClaims();
    loadRecallCampaigns();
  }, []);

  const loadReferenceData = async () => {
    try {
      const response = await api.scStaff.getReferenceData();
      if (response.success) {
        setReferenceData(response.data);
      }
    } catch (error) {
      console.error('Failed to load reference data:', error);
      // Mock data for demo
      setReferenceData({
        models: [
          { id: '1', name: 'VF8', full_name: 'VinFast VF8 Eco' },
          { id: '2', name: 'VF9', full_name: 'VinFast VF9 Plus' },
          { id: '3', name: 'VFe34', full_name: 'VinFast VFe34' }
        ],
        customers: [
          { id: '1', name: 'Nguyễn Văn A', phone: '0901234567' },
          { id: '2', name: 'Trần Thị B', phone: '0912345678' },
          { id: '3', name: 'Lê Văn C', phone: '0923456789' }
        ],
        parts: []
      });
    }
  };

  const loadWarrantyClaims = async () => {
    try {
      const response = await api.scStaff.getWarrantyClaims();
      if (response.success && response.data) {
        setWarrantyClaims(response.data);
      }
    } catch (error) {
      console.error('Failed to load warranty claims:', error);
    }
  };

  const loadRecallCampaigns = async () => {
    try {
      const response = await api.scStaff.getRecallCampaigns();
      if (response.success && response.data) {
        setRecallCampaigns(response.data);
      }
    } catch (error) {
      console.error('Failed to load recall campaigns:', error);
    }
  };

  const handleVehicleRegistration = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await fetch('/api/sc-staff/vehicles/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(vehicleForm)
      });

      const data = await response.json();
      if (data.success) {
        alert('Vehicle registered successfully!');
        const today = new Date().toISOString().split('T')[0];
        const currentYear = new Date().getFullYear().toString();
        setVehicleForm({
          vin: '',
          model_id: '',
          year: currentYear,
          color: '',
          customer_id: '',
          purchase_date: today,
          warranty_start_date: today
        });
        loadDashboardStats(); // Refresh stats
      } else {
        alert('Registration failed: ' + data.error);
      }
    } catch (error) {
      alert('Registration failed: Network error');
    } finally {
      setLoading(false);
    }
  };

  const handleWarrantyClaimSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await fetch('/api/sc-staff/warranty-claims/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(claimForm)
      });

      const data = await response.json();
      if (data.success) {
        alert(`Warranty claim created: ${data.claim_number}`);
        setClaimForm({
          vehicle_id: '',
          issue_description: '',
          symptoms: '',
          failure_date: new Date().toISOString().split('T')[0],
          failure_mileage: '',
          priority: 'medium'
        });
        loadWarrantyClaims(); // Refresh claims
        loadDashboardStats(); // Refresh stats
      } else {
        alert('Claim creation failed: ' + data.error);
      }
    } catch (error) {
      alert('Claim creation failed: Network error');
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = async () => {
    if (!searchQuery.trim()) return;
    
    setLoading(true);
    try {
      const response = await fetch(`/api/sc-staff/vehicles/search?q=${encodeURIComponent(searchQuery)}&type=${searchType}`);
      const data = await response.json();
      if (data.success) {
        setSearchResults(data.data);
      } else {
        // Mock search results for demo
        setSearchResults([
          {
            vin: 'VF3ABCDEF12345678',
            model_full_name: 'VinFast VF8 Eco',
            year: 2024,
            color: 'Đen Kim Cương',
            license_plate: '30A-12345',
            customer_name: 'Nguyễn Văn A',
            customer_phone: '0901234567',
            mileage: 5000,
            status: 'active'
          }
        ]);
      }
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status: string): string => {
    const colors: { [key: string]: string } = {
      'active': 'bg-green-100 text-green-800',
      'registered': 'bg-blue-100 text-blue-800',
      'maintenance': 'bg-yellow-100 text-yellow-800',
      'draft': 'bg-gray-100 text-gray-800',
      'submitted': 'bg-blue-100 text-blue-800',
      'under_review': 'bg-yellow-100 text-yellow-800',
      'approved': 'bg-green-100 text-green-800',
      'in_progress': 'bg-orange-100 text-orange-800',
      'completed': 'bg-green-100 text-green-800',
      'rejected': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const getPriorityColor = (priority: string): string => {
    const colors: { [key: string]: string } = {
      'low': 'bg-blue-100 text-blue-800',
      'medium': 'bg-yellow-100 text-yellow-800',
      'high': 'bg-orange-100 text-orange-800',
      'critical': 'bg-red-100 text-red-800'
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
  };

  const Badge = ({ children, className }: { children: React.ReactNode; className: string }) => (
    <span className={`px-2 py-1 text-xs font-medium rounded-full ${className}`}>
      {children}
    </span>
  );

  const Card = ({ children, className = '' }: { children: React.ReactNode; className?: string }) => (
    <div className={`bg-white rounded-lg shadow border ${className}`}>
      {children}
    </div>
  );

  const Button = ({ children, onClick, type = 'button', disabled = false, className = '' }: {
    children: React.ReactNode;
    onClick?: () => void;
    type?: 'button' | 'submit';
    disabled?: boolean;
    className?: string;
  }) => (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className={`px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 ${className}`}
    >
      {children}
    </button>
  );

  const Input = ({ value, onChange, type = 'text', placeholder = '', required = false, className = '' }: {
    value: string;
    onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    type?: string;
    placeholder?: string;
    required?: boolean;
    className?: string;
    min?: string;
    max?: string;
  }) => (
    <input
      type={type}
      value={value}
      onChange={onChange}
      placeholder={placeholder}
      required={required}
      className={`w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 ${className}`}
    />
  );

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-900">SC Staff Portal</h1>
          <p className="text-gray-600">OEM EV Warranty Management System - Service Center Operations</p>
        </div>

        {/* Navigation Tabs */}
        <div className="mb-6">
          <div className="border-b border-gray-200">
            <nav className="-mb-px flex space-x-8">
              {[
                { id: 'dashboard', name: 'Dashboard', icon: CalendarIcon },
                { id: 'registration', name: 'Vehicle Registration', icon: PlusIcon },
                { id: 'claims', name: 'Warranty Claims', icon: DocumentTextIcon },
                { id: 'search', name: 'Search Vehicles', icon: MagnifyingGlassIcon },
                { id: 'recalls', name: 'Recall Campaigns', icon: ExclamationTriangleIcon }
              ].map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex items-center gap-2 py-2 px-1 border-b-2 font-medium text-sm ${
                    activeTab === tab.id
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  <tab.icon className="h-5 w-5" />
                  {tab.name}
                </button>
              ))}
            </nav>
          </div>
        </div>

        {/* Tab Content */}
        {activeTab === 'dashboard' && (
          <div className="space-y-6">
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <Card>
                <div className="p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <CalendarIcon className="h-8 w-8 text-blue-500" />
                    </div>
                    <div className="ml-4">
                      <h3 className="text-lg font-medium text-gray-900">Today's Registrations</h3>
                      <p className="text-3xl font-bold text-blue-600">{stats.today_registrations}</p>
                      <p className="text-sm text-gray-500">New vehicles registered</p>
                    </div>
                  </div>
                </div>
              </Card>

              <Card>
                <div className="p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <ClockIcon className="h-8 w-8 text-orange-500" />
                    </div>
                    <div className="ml-4">
                      <h3 className="text-lg font-medium text-gray-900">Pending Claims</h3>
                      <p className="text-3xl font-bold text-orange-600">{stats.pending_claims}</p>
                      <p className="text-sm text-gray-500">Awaiting processing</p>
                    </div>
                  </div>
                </div>
              </Card>

              <Card>
                <div className="p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <ExclamationTriangleIcon className="h-8 w-8 text-red-500" />
                    </div>
                    <div className="ml-4">
                      <h3 className="text-lg font-medium text-gray-900">Active Recalls</h3>
                      <p className="text-3xl font-bold text-red-600">{stats.active_recalls}</p>
                      <p className="text-sm text-gray-500">Campaigns in progress</p>
                    </div>
                  </div>
                </div>
              </Card>

              <Card>
                <div className="p-6">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <TruckIcon className="h-8 w-8 text-green-500" />
                    </div>
                    <div className="ml-4">
                      <h3 className="text-lg font-medium text-gray-900">Total Vehicles</h3>
                      <p className="text-3xl font-bold text-green-600">{stats.total_vehicles}</p>
                      <p className="text-sm text-gray-500">Under management</p>
                    </div>
                  </div>
                </div>
              </Card>
            </div>

            {/* Recent Activities */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Recent Warranty Claims */}
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Warranty Claims</h3>
                  <div className="space-y-4">
                    {warrantyClaims.slice(0, 5).map((claim, index) => (
                      <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div className="flex-1">
                          <div className="font-medium">{claim.claim_number}</div>
                          <div className="text-sm text-gray-600">{claim.vin} - {claim.model_name}</div>
                          <div className="text-sm text-gray-500">{claim.issue_description?.substring(0, 50)}...</div>
                        </div>
                        <div className="flex items-center gap-2">
                          <Badge className={getPriorityColor(claim.priority)}>{claim.priority}</Badge>
                          <Badge className={getStatusColor(claim.status)}>{claim.status}</Badge>
                        </div>
                      </div>
                    ))}
                    {warrantyClaims.length === 0 && (
                      <div className="text-center text-gray-500 py-8">No warranty claims found</div>
                    )}
                  </div>
                </div>
              </Card>

              {/* Active Recalls */}
              <Card>
                <div className="p-6">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Active Recall Campaigns</h3>
                  <div className="space-y-4">
                    {recallCampaigns.map((campaign, index) => (
                      <div key={index} className="p-3 bg-gray-50 rounded-lg">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <h4 className="font-medium">{campaign.title}</h4>
                            <div className="text-sm text-gray-600">{campaign.campaign_number}</div>
                            <div className="text-sm text-gray-500 mt-1">{campaign.description?.substring(0, 60)}...</div>
                            <div className="flex gap-4 text-sm mt-2">
                              <span>Affected: {campaign.affected_vehicles}</span>
                              <span>Completed: {campaign.completed_vehicles}</span>
                            </div>
                          </div>
                          <Badge 
                            className={campaign.severity === 'critical' ? 'bg-red-100 text-red-800' : 
                                     campaign.severity === 'high' ? 'bg-orange-100 text-orange-800' : 
                                     'bg-yellow-100 text-yellow-800'}
                          >
                            {campaign.severity}
                          </Badge>
                        </div>
                      </div>
                    ))}
                    {recallCampaigns.length === 0 && (
                      <div className="text-center text-gray-500 py-8">No active recall campaigns</div>
                    )}
                  </div>
                </div>
              </Card>
            </div>
          </div>
        )}

        {/* Vehicle Registration Tab */}
        {activeTab === 'registration' && (
          <Card>
            <div className="p-6">
              <div className="flex items-center gap-2 mb-4">
                <PlusIcon className="h-6 w-6 text-blue-500" />
                <h3 className="text-lg font-medium text-gray-900">Vehicle Registration</h3>
              </div>
              <p className="text-sm text-gray-600 mb-6">Register new EV with VIN and assign parts serial numbers</p>
              
              <form onSubmit={handleVehicleRegistration} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">VIN Number *</label>
                    <Input
                      value={vehicleForm.vin}
                      onChange={(e) => setVehicleForm({...vehicleForm, vin: e.target.value})}
                      placeholder="VF3ABCDEF12345678"
                      required
                    />
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium mb-1">Model *</label>
                    <select
                      value={vehicleForm.model_id}
                      onChange={(e) => setVehicleForm({...vehicleForm, model_id: e.target.value})}
                      className="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      required
                    >
                      <option value="">Select Model</option>
                      {referenceData.models.map((model) => (
                        <option key={model.id} value={model.id}>{model.full_name}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium mb-1">Year *</label>
                    <Input
                      value={vehicleForm.year}
                      onChange={(e) => setVehicleForm({...vehicleForm, year: e.target.value})}
                      type="number"
                      min="2020"
                      max="2030"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium mb-1">Color *</label>
                    <select
                      value={vehicleForm.color}
                      onChange={(e) => setVehicleForm({...vehicleForm, color: e.target.value})}
                      className="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      required
                    >
                      <option value="">Select Color</option>
                      <option value="Đen Kim Cương">Đen Kim Cương</option>
                      <option value="Trắng Ngọc Trai">Trắng Ngọc Trai</option>
                      <option value="Xanh Đại Dương">Xanh Đại Dương</option>
                      <option value="Đỏ Quyến Rũ">Đỏ Quyến Rũ</option>
                      <option value="Xám Bạc">Xám Bạc</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium mb-1">Customer *</label>
                    <select
                      value={vehicleForm.customer_id}
                      onChange={(e) => setVehicleForm({...vehicleForm, customer_id: e.target.value})}
                      className="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      required
                    >
                      <option value="">Select Customer</option>
                      {referenceData.customers.map((customer) => (
                        <option key={customer.id} value={customer.id}>
                          {customer.name} - {customer.phone}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium mb-1">Purchase Date *</label>
                    <Input
                      value={vehicleForm.purchase_date}
                      onChange={(e) => setVehicleForm({...vehicleForm, purchase_date: e.target.value})}
                      type="date"
                      required
                    />
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium mb-1">Warranty Start Date *</label>
                    <Input
                      value={vehicleForm.warranty_start_date}
                      onChange={(e) => setVehicleForm({...vehicleForm, warranty_start_date: e.target.value})}
                      type="date"
                      required
                    />
                  </div>
                </div>

                <Button type="submit" disabled={loading} className="w-full">
                  {loading ? 'Registering...' : 'Register Vehicle'}
                </Button>
              </form>
            </div>
          </Card>
        )}

        {/* Other tabs can be added here similarly */}
        {activeTab === 'search' && (
          <Card>
            <div className="p-6">
              <div className="flex items-center gap-2 mb-4">
                <MagnifyingGlassIcon className="h-6 w-6 text-blue-500" />
                <h3 className="text-lg font-medium text-gray-900">Vehicle Search</h3>
              </div>
              <p className="text-sm text-gray-600 mb-6">Search vehicles by VIN, customer name, or license plate</p>
              
              <div className="flex gap-2 mb-4">
                <select 
                  value={searchType}
                  onChange={(e) => setSearchType(e.target.value)}
                  className="p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="all">All Fields</option>
                  <option value="vin">VIN</option>
                  <option value="customer">Customer Name</option>
                  <option value="license_plate">License Plate</option>
                </select>
                <Input
                  placeholder="Enter search query..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="flex-1"
                />
                <Button onClick={handleSearch} disabled={loading}>
                  {loading ? 'Searching...' : 'Search'}
                </Button>
              </div>
              
              <div className="space-y-4">
                {searchResults.map((vehicle, index) => (
                  <div key={index} className="p-4 border rounded-lg bg-white">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <div className="font-semibold">{vehicle.vin}</div>
                        <div className="text-sm text-gray-600">{vehicle.model_full_name}</div>
                        <div className="text-sm text-gray-600">Year: {vehicle.year} | Color: {vehicle.color}</div>
                        <div className="text-sm text-gray-600">License: {vehicle.license_plate || 'Not assigned'}</div>
                      </div>
                      <div>
                        <div className="text-sm"><strong>Customer:</strong> {vehicle.customer_name}</div>
                        <div className="text-sm"><strong>Phone:</strong> {vehicle.customer_phone}</div>
                        <div className="text-sm"><strong>Mileage:</strong> {vehicle.mileage?.toLocaleString()} km</div>
                        <Badge className={getStatusColor(vehicle.status)}>{vehicle.status}</Badge>
                      </div>
                    </div>
                  </div>
                ))}
                {searchResults.length === 0 && searchQuery && !loading && (
                  <div className="text-center text-gray-500 py-8">No vehicles found for "{searchQuery}"</div>
                )}
                {!searchQuery && (
                  <div className="text-center text-gray-500 py-8">Enter search criteria to find vehicles</div>
                )}
              </div>
            </div>
          </Card>
        )}
      </div>
    </div>
  );
}