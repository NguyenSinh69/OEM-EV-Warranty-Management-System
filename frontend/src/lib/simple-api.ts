// Simple working API without dependencies
const API_BASE_URL = "http://localhost:8004";

export const api = {
  async get(endpoint: string) {
    const response = await fetch(`${API_BASE_URL}${endpoint}`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  },

  async post(endpoint: string, data: any) {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    });
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  },
};

// Export functions for easy use
export const getDashboardSummary = () => api.get("/api/dashboard/summary");
export const loginUser = (username: string, password: string) =>
  api.post("/api/login", { username, password });
export const getUsers = () => api.get("/api/users");

export default api;
