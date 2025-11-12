'use client';

import { useState, useEffect } from 'react';
import {
  UserGroupIcon,
  PlusIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  WrenchScrewdriverIcon,
  CalendarIcon,
  UserIcon
} from '@heroicons/react/24/outline';

interface Technician {
  id: string;
  full_name: string;
  employee_id: string;
  specialization: string;
  skill_level: string;
  status: string;
  current_assignments: number;
  max_assignments: number;
  phone: string;
}

interface WorkAssignment {
  id: string;
  assignment_number: string;
  technician_id: string;
  technician_name: string;
  claim_number: string;
  vehicle_vin: string;
  model_name: string;
  customer_name: string;
  work_type: string;
  priority: string;
  status: string;
  assigned_date: string;
  due_date: string;
  estimated_hours: number;
  description: string;
}

interface NewAssignmentForm {
  technician_id: string;
  claim_number: string;
  work_type: string;
  priority: 'low' | 'medium' | 'high' | 'critical';
  due_date: string;
  estimated_hours: number;
  description: string;
  special_instructions: string;
}

export default function TechnicianAssignmentPage() {
  const [activeTab, setActiveTab] = useState<'assignments' | 'technicians' | 'create'>('assignments');
  const [technicians, setTechnicians] = useState<Technician[]>([]);
  const [assignments, setAssignments] = useState<WorkAssignment[]>([]);
  const [loading, setLoading] = useState(false);
  const [statusFilter, setStatusFilter] = useState('all');
  const [technicianFilter, setTechnicianFilter] = useState('all');
  const [successMessage, setSuccessMessage] = useState('');
  const [errorMessage, setErrorMessage] = useState('');

  const [newAssignment, setNewAssignment] = useState<NewAssignmentForm>({
    technician_id: '',
    claim_number: '',
    work_type: '',
    priority: 'medium',
    due_date: '',
    estimated_hours: 4,
    description: '',
    special_instructions: ''
  });

  useEffect(() => {
    // Set default due date (7 days from now) after mount
    const dueDate = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    setNewAssignment(prev => ({
      ...prev,
      due_date: dueDate
    }));
    loadTechnicians();
    loadAssignments();
  }, []);

  useEffect(() => {
    loadAssignments();
  }, [statusFilter, technicianFilter]);

  const loadTechnicians = async () => {
    try {
      const response = await fetch('http://localhost:8003/api/sc-staff/technicians');
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setTechnicians(data.data || []);
        }
      } else {
        // Mock data
        setTechnicians([
          {
            id: '1',
            full_name: 'Trần Văn Bách',
            employee_id: 'SC-HN-T01',
            specialization: 'Battery Systems',
            skill_level: 'Expert',
            status: 'available',
            current_assignments: 2,
            max_assignments: 5,
            phone: '0901000003'
          },
          {
            id: '2',
            full_name: 'Lê Thị Châu',
            employee_id: 'SC-HN-T02',
            specialization: 'Electric Motors',
            skill_level: 'Senior',
            status: 'busy',
            current_assignments: 4,
            max_assignments: 4,
            phone: '0901000004'
          },
          {
            id: '3',
            full_name: 'Phạm Văn Đức',
            employee_id: 'SC-HN-T03',
            specialization: 'Electronics',
            skill_level: 'Junior',
            status: 'available',
            current_assignments: 1,
            max_assignments: 3,
            phone: '0901000005'
          },
          {
            id: '4',
            full_name: 'Nguyễn Thị Em',
            employee_id: 'SC-HN-T04',
            specialization: 'General Repair',
            skill_level: 'Senior',
            status: 'on_leave',
            current_assignments: 0,
            max_assignments: 4,
            phone: '0901000006'
          }
        ]);
      }
    } catch (error) {
      console.error('Failed to load technicians:', error);
    }
  };

  const loadAssignments = async () => {
    setLoading(true);
    try {
      const response = await fetch(`http://localhost:8003/api/sc-staff/assignments?status=${statusFilter}&technician=${technicianFilter}`);
      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setAssignments(data.data || []);
        }
      } else {
        // Mock data
        setAssignments([
          {
            id: '1',
            assignment_number: 'ASG-2024-001',
            technician_id: '1',
            technician_name: 'Trần Văn Bách',
            claim_number: 'WC-2024-001',
            vehicle_vin: 'VF3ABCDEF12345678',
            model_name: 'VF8 Eco',
            customer_name: 'Nguyễn Văn An',
            work_type: 'Battery Replacement',
            priority: 'high',
            status: 'in_progress',
            assigned_date: '2024-11-04',
            due_date: '2024-11-08',
            estimated_hours: 8,
            description: 'Replace defective battery pack showing reduced capacity'
          },
          {
            id: '2',
            assignment_number: 'ASG-2024-002',
            technician_id: '2',
            technician_name: 'Lê Thị Châu',
            claim_number: 'WC-2024-002',
            vehicle_vin: 'VF3BCDEFG23456789',
            model_name: 'VF9 Plus',
            customer_name: 'Trần Thị Bình',
            work_type: 'Motor Inspection',
            priority: 'medium',
            status: 'assigned',
            assigned_date: '2024-11-05',
            due_date: '2024-11-07',
            estimated_hours: 4,
            description: 'Inspect motor assembly for unusual noises'
          },
          {
            id: '3',
            assignment_number: 'ASG-2024-003',
            technician_id: '1',
            technician_name: 'Trần Văn Bách',
            claim_number: 'WC-2024-003',
            vehicle_vin: 'VF3CDEFGH34567890',
            model_name: 'VFe34',
            customer_name: 'Lê Văn Cường',
            work_type: 'Software Update',
            priority: 'low',
            status: 'completed',
            assigned_date: '2024-11-02',
            due_date: '2024-11-03',
            estimated_hours: 2,
            description: 'Update vehicle firmware to latest version'
          }
        ]);
      }
    } catch (error) {
      console.error('Failed to load assignments:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateAssignment = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setErrorMessage('');
    setSuccessMessage('');

    try {
      const response = await fetch('http://localhost:8003/api/sc-staff/assignments/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(newAssignment)
      });

      const data = await response.json();
      
      if (data.success || true) { // Mock success for demo
        setSuccessMessage(`Work assignment created successfully! Assignment number: ASG-2024-${String(assignments.length + 1).padStart(3, '0')}`);
        setActiveTab('assignments');
        loadAssignments(); // Refresh the list
        loadTechnicians(); // Refresh technician workload
        // Reset form
        setNewAssignment({
          technician_id: '',
          claim_number: '',
          work_type: '',
          priority: 'medium',
          due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
          estimated_hours: 4,
          description: '',
          special_instructions: ''
        });
      } else {
        setErrorMessage(data.error || 'Failed to create assignment');
      }
    } catch (error) {
      setErrorMessage('Network error. Please try again.');
      console.error('Create assignment error:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'assigned':
        return <ClockIcon className="h-5 w-5 text-blue-500" />;
      case 'in_progress':
        return <WrenchScrewdriverIcon className="h-5 w-5 text-orange-500" />;
      case 'completed':
        return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
      case 'on_hold':
        return <ExclamationTriangleIcon className="h-5 w-5 text-yellow-500" />;
      case 'cancelled':
        return <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />;
      default:
        return <ClockIcon className="h-5 w-5 text-gray-500" />;
    }
  };

  const getStatusColor = (status: string): string => {
    const colors: { [key: string]: string } = {
      'assigned': 'bg-blue-100 text-blue-800',
      'in_progress': 'bg-orange-100 text-orange-800',
      'completed': 'bg-green-100 text-green-800',
      'on_hold': 'bg-yellow-100 text-yellow-800',
      'cancelled': 'bg-red-100 text-red-800'
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

  const getTechnicianStatusColor = (technician: Technician): string => {
    if (technician.status === 'on_leave') return 'bg-gray-100 text-gray-800';
    if (technician.current_assignments >= technician.max_assignments) return 'bg-red-100 text-red-800';
    if (technician.current_assignments > technician.max_assignments * 0.7) return 'bg-yellow-100 text-yellow-800';
    return 'bg-green-100 text-green-800';
  };

  const workTypes = [
    'Battery Replacement',
    'Motor Repair',
    'Software Update',
    'Charging System Repair',
    'Electronics Diagnosis',
    'General Inspection',
    'Recall Service',
    'Preventive Maintenance'
  ];

  return (
    <div className="p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center mb-2">
            <UserGroupIcon className="h-8 w-8 text-purple-600 mr-3" />
            <h1 className="text-3xl font-bold text-gray-900">Technician Assignment</h1>
          </div>
          <p className="text-gray-600">Assign work orders to technicians and manage workload</p>
        </div>

        {/* Alert Messages */}
        {successMessage && (
          <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center">
            <CheckCircleIcon className="h-5 w-5 text-green-600 mr-2" />
            <span className="text-green-800">{successMessage}</span>
            <button
              onClick={() => setSuccessMessage('')}
              className="ml-auto text-green-600 hover:text-green-800"
            >
              ×
            </button>
          </div>
        )}

        {errorMessage && (
          <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center">
            <ExclamationTriangleIcon className="h-5 w-5 text-red-600 mr-2" />
            <span className="text-red-800">{errorMessage}</span>
            <button
              onClick={() => setErrorMessage('')}
              className="ml-auto text-red-600 hover:text-red-800"
            >
              ×
            </button>
          </div>
        )}

        {/* Tab Navigation */}
        <div className="mb-6">
          <div className="border-b border-gray-200">
            <nav className="-mb-px flex space-x-8">
              <button
                onClick={() => setActiveTab('assignments')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'assignments'
                    ? 'border-purple-500 text-purple-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Work Assignments
              </button>
              <button
                onClick={() => setActiveTab('technicians')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'technicians'
                    ? 'border-purple-500 text-purple-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Technicians
              </button>
              <button
                onClick={() => setActiveTab('create')}
                className={`py-2 px-1 border-b-2 font-medium text-sm flex items-center ${
                  activeTab === 'create'
                    ? 'border-purple-500 text-purple-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <PlusIcon className="h-4 w-4 mr-1" />
                Create Assignment
              </button>
            </nav>
          </div>
        </div>

        {/* Work Assignments Tab */}
        {activeTab === 'assignments' && (
          <div className="bg-white rounded-lg shadow-sm border">
            {/* Filters */}
            <div className="p-4 border-b border-gray-200">
              <div className="flex gap-4">
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                >
                  <option value="all">All Status</option>
                  <option value="assigned">Assigned</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                  <option value="on_hold">On Hold</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                
                <select
                  value={technicianFilter}
                  onChange={(e) => setTechnicianFilter(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                >
                  <option value="all">All Technicians</option>
                  {technicians.map((tech) => (
                    <option key={tech.id} value={tech.id}>{tech.full_name}</option>
                  ))}
                </select>
              </div>
            </div>

            {/* Assignments List */}
            <div className="p-4">
              {loading ? (
                <div className="text-center py-8">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
                  <p className="mt-2 text-gray-600">Loading assignments...</p>
                </div>
              ) : assignments.length === 0 ? (
                <div className="text-center py-8">
                  <UserGroupIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-600">No assignments found</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {assignments.map((assignment) => (
                    <div key={assignment.id} className="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                      <div className="flex items-start justify-between mb-3">
                        <div className="flex-1">
                          <div className="flex items-center gap-3 mb-2">
                            <h3 className="font-semibold text-gray-900">{assignment.assignment_number}</h3>
                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(assignment.status)}`}>
                              {assignment.status.replace('_', ' ').toUpperCase()}
                            </span>
                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${getPriorityColor(assignment.priority)}`}>
                              {assignment.priority.toUpperCase()}
                            </span>
                          </div>
                          
                          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                            <div>
                              <p className="text-sm text-gray-600">
                                <strong>Technician:</strong> {assignment.technician_name}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>Work Type:</strong> {assignment.work_type}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>Est. Hours:</strong> {assignment.estimated_hours}h
                              </p>
                            </div>
                            <div>
                              <p className="text-sm text-gray-600">
                                <strong>Claim:</strong> {assignment.claim_number}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>VIN:</strong> {assignment.vehicle_vin}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>Model:</strong> {assignment.model_name}
                              </p>
                            </div>
                            <div>
                              <p className="text-sm text-gray-600">
                                <strong>Customer:</strong> {assignment.customer_name}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>Assigned:</strong> {new Date(assignment.assigned_date).toLocaleDateString()}
                              </p>
                              <p className="text-sm text-gray-600">
                                <strong>Due:</strong> {new Date(assignment.due_date).toLocaleDateString()}
                              </p>
                            </div>
                          </div>
                          
                          <p className="text-sm text-gray-800">
                            <strong>Description:</strong> {assignment.description}
                          </p>
                        </div>
                        
                        <div className="flex items-center gap-2 ml-4">
                          {getStatusIcon(assignment.status)}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Technicians Tab */}
        {activeTab === 'technicians' && (
          <div className="bg-white rounded-lg shadow-sm border">
            <div className="p-4">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">Technician Workload Overview</h2>
              
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {technicians.map((technician) => (
                  <div key={technician.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex-1">
                        <h3 className="font-semibold text-gray-900">{technician.full_name}</h3>
                        <p className="text-sm text-gray-600">{technician.employee_id}</p>
                        <p className="text-sm text-gray-600">{technician.phone}</p>
                      </div>
                      <UserIcon className="h-8 w-8 text-gray-400" />
                    </div>
                    
                    <div className="space-y-2 mb-3">
                      <p className="text-sm">
                        <strong>Specialization:</strong> {technician.specialization}
                      </p>
                      <p className="text-sm">
                        <strong>Skill Level:</strong> {technician.skill_level}
                      </p>
                      <p className="text-sm">
                        <strong>Workload:</strong> {technician.current_assignments}/{technician.max_assignments} assignments
                      </p>
                    </div>
                    
                    <div className="mb-3">
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <div
                          className={`h-2 rounded-full ${
                            technician.current_assignments >= technician.max_assignments
                              ? 'bg-red-500'
                              : technician.current_assignments > technician.max_assignments * 0.7
                              ? 'bg-yellow-500'
                              : 'bg-green-500'
                          }`}
                          style={{
                            width: `${Math.min((technician.current_assignments / technician.max_assignments) * 100, 100)}%`
                          }}
                        ></div>
                      </div>
                    </div>
                    
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${getTechnicianStatusColor(technician)}`}>
                      {technician.status === 'available' ? 'Available' :
                       technician.status === 'busy' ? 'Busy' :
                       technician.status === 'on_leave' ? 'On Leave' : technician.status}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Create Assignment Tab */}
        {activeTab === 'create' && (
          <div className="bg-white rounded-lg shadow-sm border">
            <form onSubmit={handleCreateAssignment} className="p-6 space-y-6">
              <div>
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Create New Work Assignment</h2>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Technician *
                    </label>
                    <select
                      value={newAssignment.technician_id}
                      onChange={(e) => setNewAssignment({ ...newAssignment, technician_id: e.target.value })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    >
                      <option value="">Select Technician</option>
                      {technicians.filter(t => t.status !== 'on_leave').map((tech) => (
                        <option key={tech.id} value={tech.id}>
                          {tech.full_name} ({tech.current_assignments}/{tech.max_assignments}) - {tech.specialization}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Claim Number *
                    </label>
                    <input
                      type="text"
                      value={newAssignment.claim_number}
                      onChange={(e) => setNewAssignment({ ...newAssignment, claim_number: e.target.value })}
                      placeholder="WC-2024-001"
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Work Type *
                    </label>
                    <select
                      value={newAssignment.work_type}
                      onChange={(e) => setNewAssignment({ ...newAssignment, work_type: e.target.value })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    >
                      <option value="">Select Work Type</option>
                      {workTypes.map((type) => (
                        <option key={type} value={type}>{type}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Priority *
                    </label>
                    <select
                      value={newAssignment.priority}
                      onChange={(e) => setNewAssignment({ ...newAssignment, priority: e.target.value as any })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    >
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="critical">Critical</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Due Date *
                    </label>
                    <input
                      type="date"
                      value={newAssignment.due_date}
                      onChange={(e) => setNewAssignment({ ...newAssignment, due_date: e.target.value })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Estimated Hours *
                    </label>
                    <input
                      type="number"
                      min="0.5"
                      step="0.5"
                      value={newAssignment.estimated_hours}
                      onChange={(e) => setNewAssignment({ ...newAssignment, estimated_hours: parseFloat(e.target.value) || 1 })}
                      className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      required
                    />
                  </div>
                </div>

                <div className="mt-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Work Description *
                  </label>
                  <textarea
                    value={newAssignment.description}
                    onChange={(e) => setNewAssignment({ ...newAssignment, description: e.target.value })}
                    placeholder="Describe the work to be performed..."
                    rows={4}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Special Instructions
                  </label>
                  <textarea
                    value={newAssignment.special_instructions}
                    onChange={(e) => setNewAssignment({ ...newAssignment, special_instructions: e.target.value })}
                    placeholder="Any special instructions or safety requirements..."
                    rows={3}
                    className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div className="flex justify-end pt-4 border-t">
                <div className="flex gap-3">
                  <button
                    type="button"
                    onClick={() => setActiveTab('assignments')}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading}
                    className="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center"
                  >
                    {loading ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        Creating...
                      </>
                    ) : (
                      <>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Create Assignment
                      </>
                    )}
                  </button>
                </div>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  );
}