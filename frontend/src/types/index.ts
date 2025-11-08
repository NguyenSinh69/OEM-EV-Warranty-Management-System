// Types for the EVM Warranty System
export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'evm_staff' | 'sc_staff' | 'technician' | 'customer';
  avatar?: string;
  service_center_id?: number;
}

export interface Vehicle {
  id: number;
  vin: string;
  model: string;
  year: number;
  color: string;
  customer_id: number;
  purchase_date: string;
  warranty_start_date: string;
  warranty_end_date: string;
  status: 'active' | 'inactive' | 'recalled';
  mileage: number;
  battery_capacity: string;
  motor_power: string;
  customer?: Customer;
}

export interface Customer {
  id: number;
  name: string;
  email: string;
  phone: string;
  address: string;
  date_of_birth: string;
  id_number: string;
  status: 'active' | 'inactive';
  vehicles?: Vehicle[];
}

export interface WarrantyClaim {
  id: number;
  claim_number: string;
  customer_id: number;
  vehicle_vin: string;
  description: string;
  issue_type: 'battery' | 'motor' | 'electrical' | 'mechanical' | 'software';
  priority: 'low' | 'medium' | 'high' | 'critical';
  status: 'pending' | 'in_progress' | 'approved' | 'rejected' | 'completed' | 'closed';
  created_at: string;
  updated_at?: string;
  estimated_cost: number;
  actual_cost?: number;
  technician_id?: number;
  service_center_id?: number;
  attachments?: ClaimAttachment[];
  customer?: Customer;
  vehicle?: Vehicle;
  technician?: User;
}

export interface ClaimAttachment {
  id: number;
  claim_id: number;
  file_name: string;
  file_type: 'image' | 'video' | 'document';
  file_url: string;
  uploaded_at: string;
}

export interface ServiceCenter {
  id: number;
  name: string;
  address: string;
  phone: string;
  email: string;
  manager_name: string;
  status: 'active' | 'inactive';
  region: string;
  technicians?: User[];
}

export interface Part {
  id: number;
  part_number: string;
  name: string;
  description: string;
  category: string;
  price: number;
  warranty_months: number;
  compatible_models: string[];
}

export interface Inventory {
  id: number;
  service_center_id: number;
  part_id: number;
  quantity: number;
  min_quantity: number;
  max_quantity: number;
  last_updated: string;
  part?: Part;
  service_center?: ServiceCenter;
}

export interface Notification {
  id: number;
  user_id: number;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'error' | 'success';
  read: boolean;
  created_at: string;
}

export interface DashboardStats {
  total_vehicles: number;
  active_claims: number;
  pending_approvals: number;
  completed_claims_today: number;
  total_customers: number;
  total_service_centers: number;
  low_stock_items: number;
  critical_claims: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message: string;
  errors?: string[];
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface LoginResponse {
  customer: User;
  token: string;
  token_type: string;
  expires_in: number;
}