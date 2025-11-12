// frontend/src/lib/api.ts
import axios from 'axios';
import Cookies from 'js-cookie';

// Nếu bạn chưa có các type riêng, giữ any để tránh lỗi build
type ApiResponse<T = any> = T;
type LoginRequest = any;
type LoginResponse = any;
type User = any;
type Vehicle = any;
type Customer = any;
type WarrantyClaim = any;
type DashboardStats = any;

/** Ưu tiên KONG URL, rồi tới API_BASE_URL, cuối cùng default localhost:8000 */
export const API_BASE_URL =
  process.env.NEXT_PUBLIC_KONG_API_URL ||
  process.env.NEXT_PUBLIC_API_BASE_URL ||
  'http://localhost:8000';

/** Axios client gọi QUA KONG */
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
});

/** Gắn JWT từ cookie nếu có */
apiClient.interceptors.request.use((config) => {
  const token = Cookies.get('auth_token');
  if (token) {
    config.headers = config.headers ?? {};
    (config.headers as any).Authorization = `Bearer ${token}`;
  }
  return config;
});

/** 401 -> xoá token & chuyển về /login */
apiClient.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      Cookies.remove('auth_token');
      if (typeof window !== 'undefined') window.location.href = '/login';
    }
    return Promise.reject(err);
  }
);

/** Helper GET đơn giản dùng cho SWR/fetch */
export async function apiGet(path: string, init?: RequestInit) {
  const res = await fetch(`${API_BASE_URL}${path}`, { cache: 'no-store', ...init });
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}

export const api = {
  // ================= AUTH =================
  async login(credentials: LoginRequest): Promise<ApiResponse<LoginResponse>> {
    const res = await apiClient.post(`/api/auth/login`, credentials);
    return res.data;
  },

  async register(userData: Partial<User>): Promise<ApiResponse<User>> {
    const res = await apiClient.post(`/api/auth/register`, userData);
    return res.data;
  },

  async logout(): Promise<void> {
    Cookies.remove('auth_token');
  },

  // ================ CUSTOMERS ==============
  async getCustomers(): Promise<ApiResponse<Customer[]>> {
    const res = await apiClient.get(`/api/customers`);
    return res.data;
  },

  async getCustomer(id: number | string): Promise<ApiResponse<Customer>> {
    const res = await apiClient.get(`/api/customers/${id}`);
    return res.data;
  },

  // ===== VEHICLES cho Dashboard (ownerId) ==
  // GET /api/customer/vehicles?ownerId=<id>
  async getCustomerVehiclesByOwner(
    ownerId: number | string
  ): Promise<ApiResponse<{ items: Vehicle[] } | Vehicle[]>> {
    const res = await apiClient.get(`/api/customer/vehicles`, { params: { ownerId } });
    return res.data;
  },

  // ================ CLAIMS (Customer) ======
  // List: GET /api/customer/claims?customer_id=&status=&page=&limit=
  async listCustomerClaims(params: {
    customer_id: number | string;
    status?: string;
    page?: number;
    limit?: number;
  }): Promise<ApiResponse<{ items: WarrantyClaim[] } | WarrantyClaim[]>> {
    const res = await apiClient.get(`/api/customer/claims`, { params });
    return res.data;
  },

  // Detail: GET /api/customer/claims/{id}?customer_id=
  async getCustomerClaim(
    id: string,
    customer_id: number | string
  ): Promise<ApiResponse<WarrantyClaim>> {
    const res = await apiClient.get(`/api/customer/claims/${id}`, { params: { customer_id } });
    return res.data;
  },

  // Create: POST /api/customer/claims
  async createCustomerClaim(payload: {
    customer_id: number | string;
    vin: string;
    description?: string;
  }): Promise<ApiResponse<WarrantyClaim>> {
    const res = await apiClient.post(`/api/customer/claims`, payload);
    return res.data;
  },

  // Upload: POST /api/customer/claims/{id}/attachments (multipart)
  async uploadClaimAttachments(
    id: string,
    customer_id: number | string,
    files: File[] | FileList
  ): Promise<ApiResponse<any>> {
    const form = new FormData();
    form.append('customer_id', String(customer_id));
    const list = Array.isArray(files) ? files : Array.from(files);
    list.forEach((f) => form.append('files[]', f));
    const res = await apiClient.post(`/api/customer/claims/${id}/attachments`, form);
    return res.data;
  },

  // ================= ADMIN =================
  async getDashboardStats(): Promise<ApiResponse<DashboardStats>> {
    const res = await apiClient.get(`/api/admin/stats`);
    return res.data;
  },

  async getSystemHealth(): Promise<ApiResponse<any>> {
    const res = await apiClient.get(`/api/admin/health`);
    return res.data;
  },

  // ============== NOTIFICATIONS ============
  async getNotifications(): Promise<ApiResponse<any[]>> {
    const res = await apiClient.get(`/api/notifications`);
    return res.data;
  },

  async sendNotification(notificationData: any): Promise<ApiResponse<any>> {
    const res = await apiClient.post(`/api/notifications`, notificationData);
    return res.data;
  },

  // ================ HEALTH =================
  async checkGatewayHealth(): Promise<any> {
    const res = await apiClient.get(`/api/health`);
    return res.data;
  },
};
