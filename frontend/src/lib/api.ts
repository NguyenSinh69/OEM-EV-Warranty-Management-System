import axios from 'axios';
import Cookies from 'js-cookie';
import { ApiResponse, LoginRequest, LoginResponse, User, Vehicle, Customer, WarrantyClaim, DashboardStats } from '@/types';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost';

// API client configuration
const apiClient = axios.create({
  timeout: 10000,
});

// Request interceptor to add auth token
apiClient.interceptors.request.use((config) => {
  const token = Cookies.get('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      Cookies.remove('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export const api = {
  // Authentication
  async login(credentials: LoginRequest): Promise<ApiResponse<LoginResponse>> {
    const response = await apiClient.post(`${API_BASE_URL}:8001/api/auth/login`, credentials);
    return response.data;
  },

  async register(userData: any): Promise<ApiResponse<User>> {
    const response = await apiClient.post(`${API_BASE_URL}:8001/api/auth/register`, userData);
    return response.data;
  },

  async logout(): Promise<void> {
    Cookies.remove('auth_token');
  },

  // Customer Service (Port 8001)
  async getCustomers(): Promise<ApiResponse<Customer[]>> {
    const response = await apiClient.get(`${API_BASE_URL}:8001/api/customers`);
    return response.data;
  },

  async getCustomer(id: number): Promise<ApiResponse<Customer>> {
    const response = await apiClient.get(`${API_BASE_URL}:8001/api/customers/${id}`);
    return response.data;
  },

  async getCustomerVehicles(customerId: number): Promise<ApiResponse<Vehicle[]>> {
    const response = await apiClient.get(`${API_BASE_URL}:8001/api/customers/${customerId}/vehicles`);
    return response.data;
  },

  // Vehicle Service (Port 8003)
  async getVehicles(): Promise<ApiResponse<Vehicle[]>> {
    const response = await apiClient.get(`${API_BASE_URL}:8003/api/vehicles`);
    return response.data;
  },

  async getVehicleByVin(vin: string): Promise<ApiResponse<Vehicle>> {
    const response = await apiClient.get(`${API_BASE_URL}:8003/api/vehicles/${vin}`);
    return response.data;
  },

  async getVehicleWarranty(vin: string): Promise<ApiResponse<any>> {
    const response = await apiClient.get(`${API_BASE_URL}:8003/api/vehicles/${vin}/warranty`);
    return response.data;
  },

  async registerVehicle(vehicleData: any): Promise<ApiResponse<Vehicle>> {
    const response = await apiClient.post(`${API_BASE_URL}:8003/api/vehicles`, vehicleData);
    return response.data;
  },

  // Warranty Service (Port 8002)
  async getWarrantyClaims(): Promise<ApiResponse<WarrantyClaim[]>> {
    const response = await apiClient.get(`${API_BASE_URL}:8002/api/warranty/claims`);
    return response.data;
  },

  async getWarrantyClaim(id: number): Promise<ApiResponse<WarrantyClaim>> {
    const response = await apiClient.get(`${API_BASE_URL}:8002/api/warranty/claims/${id}`);
    return response.data;
  },

  async createWarrantyClaim(claimData: any): Promise<ApiResponse<WarrantyClaim>> {
    const response = await apiClient.post(`${API_BASE_URL}:8002/api/warranty/claims`, claimData);
    return response.data;
  },

  async updateWarrantyClaim(id: number, claimData: any): Promise<ApiResponse<WarrantyClaim>> {
    const response = await apiClient.put(`${API_BASE_URL}:8002/api/warranty/claims/${id}`, claimData);
    return response.data;
  },

  async approveWarrantyClaim(id: number): Promise<ApiResponse<WarrantyClaim>> {
    const response = await apiClient.put(`${API_BASE_URL}:8002/api/warranty/claims/${id}/approve`);
    return response.data;
  },

  async rejectWarrantyClaim(id: number, reason: string): Promise<ApiResponse<WarrantyClaim>> {
    const response = await apiClient.put(`${API_BASE_URL}:8002/api/warranty/claims/${id}/reject`, { reason });
    return response.data;
  },

  // Admin Service (Port 8004)
  async getDashboardStats(): Promise<ApiResponse<DashboardStats>> {
    const response = await apiClient.get(`${API_BASE_URL}:8004/api/admin/stats`);
    return response.data;
  },

  async getSystemHealth(): Promise<ApiResponse<any>> {
    const response = await apiClient.get(`${API_BASE_URL}:8004/api/admin/health`);
    return response.data;
  },

  // Notification Service (Port 8005)
  async getNotifications(): Promise<ApiResponse<any[]>> {
    const response = await apiClient.get(`${API_BASE_URL}:8005/api/notifications`);
    return response.data;
  },

  async sendNotification(notificationData: any): Promise<ApiResponse<any>> {
    const response = await apiClient.post(`${API_BASE_URL}:8005/api/notifications`, notificationData);
    return response.data;
  },

  // SC Staff APIs
  scStaff: {
    // Dashboard
    getDashboardStats: async (): Promise<ApiResponse<any>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8003/api/sc-staff/dashboard/stats`);
      return response.data;
    },

    // Vehicle Registration
    registerVehicle: async (vehicleData: any): Promise<ApiResponse<any>> => {
      const response = await apiClient.post(`${API_BASE_URL}:8003/api/sc-staff/vehicles/register`, vehicleData);
      return response.data;
    },

    searchVehicles: async (query: string, searchType: string = 'all'): Promise<ApiResponse<any[]>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8003/api/sc-staff/vehicles/search`, {
        params: { query, search_type: searchType }
      });
      return response.data;
    },

    getReferenceData: async (): Promise<ApiResponse<any>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8003/api/sc-staff/reference-data`);
      return response.data;
    },

    // Warranty Claims
    createClaim: async (claimData: any): Promise<ApiResponse<any>> => {
      const response = await apiClient.post(`${API_BASE_URL}:8003/api/sc-staff/warranty-claims/create`, claimData);
      return response.data;
    },

    getWarrantyClaims: async (status?: string): Promise<ApiResponse<any[]>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8003/api/sc-staff/warranty-claims`, {
        params: status ? { status } : {}
      });
      return response.data;
    },

    // Recall Campaigns
    getRecallCampaigns: async (): Promise<ApiResponse<any[]>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8003/api/sc-staff/recalls`);
      return response.data;
    },

    // File Upload
    uploadFile: async (file: File, category: string = 'claims'): Promise<ApiResponse<any>> => {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('category', category);

      const response = await apiClient.post(`${API_BASE_URL}:8006/api/upload/file`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      return response.data;
    },
  },

  // Customer Portal APIs
  customer: {
    getMyVehicles: async (): Promise<ApiResponse<Vehicle[]>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8001/api/customer/vehicles`);
      return response.data;
    },

    getMyClaims: async (status?: string): Promise<ApiResponse<WarrantyClaim[]>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8001/api/customer/claims`, {
        params: status ? { status } : {}
      });
      return response.data;
    },

    createClaim: async (claimData: any): Promise<ApiResponse<WarrantyClaim>> => {
      const response = await apiClient.post(`${API_BASE_URL}:8001/api/customer/claims`, claimData);
      return response.data;
    },

    getClaimDetails: async (claimId: number): Promise<ApiResponse<WarrantyClaim>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8001/api/customer/claims/${claimId}`);
      return response.data;
    },

    bookAppointment: async (appointmentData: any): Promise<ApiResponse<any>> => {
      const response = await apiClient.post(`${API_BASE_URL}:8001/api/customer/appointments`, appointmentData);
      return response.data;
    },

    getMyAppointments: async (): Promise<ApiResponse<any[]>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8001/api/customer/appointments`);
      return response.data;
    },

    getMyNotifications: async (): Promise<ApiResponse<any[]>> => {
      const response = await apiClient.get(`${API_BASE_URL}:8001/api/customer/notifications`);
      return response.data;
    },

    markNotificationAsRead: async (notificationId: number): Promise<ApiResponse<any>> => {
      const response = await apiClient.put(`${API_BASE_URL}:8001/api/customer/notifications/${notificationId}/read`);
      return response.data;
    },

    deleteNotification: async (notificationId: number): Promise<ApiResponse<any>> => {
      const response = await apiClient.delete(`${API_BASE_URL}:8001/api/customer/notifications/${notificationId}`);
      return response.data;
    },
  },

  // Health checks for all services
  async checkServicesHealth(): Promise<Record<string, any>> {
    const services = [
      { name: 'customer', port: 8001 },
      { name: 'warranty', port: 8002 },
      { name: 'vehicle', port: 8003 },
      { name: 'admin', port: 8004 },
      { name: 'notification', port: 8005 },
      { name: 'upload', port: 8006 },
    ];

    const healthChecks = await Promise.allSettled(
      services.map(async (service) => {
        const healthPath = service.name === 'upload' ? '/api/upload/health' : '/api/health';
        const response = await apiClient.get(`${API_BASE_URL}:${service.port}${healthPath}`);
        return { [service.name]: response.data };
      })
    );

    return healthChecks.reduce((acc, result, index) => {
      if (result.status === 'fulfilled') {
        return { ...acc, ...result.value };
      } else {
        return { ...acc, [services[index].name]: { status: 'error', error: result.reason } };
      }
    }, {});
  },
};