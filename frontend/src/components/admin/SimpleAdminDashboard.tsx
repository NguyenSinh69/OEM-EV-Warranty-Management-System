"use client";

import { useState, useEffect } from "react";

// Define types
interface DashboardData {
  total_users: number;
  total_service_centers: number;
  total_warranties: number;
  total_cost: number;
}

// Simple fetch function without dependencies
async function fetchDashboardData(): Promise<DashboardData> {
  const response = await fetch("http://localhost:8004/api/dashboard/summary");
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  return response.json();
}

export default function SimpleAdminDashboard() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      setError(null);
      const result = await fetchDashboardData();
      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Unknown error occurred");
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div style={{ padding: "20px", textAlign: "center" }}>
        <h1>Admin Dashboard</h1>
        <p>Đang tải dữ liệu...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div style={{ padding: "20px", textAlign: "center", color: "red" }}>
        <h1>Admin Dashboard</h1>
        <p>Lỗi: {error}</p>
        <button
          onClick={loadDashboardData}
          style={{
            padding: "10px 20px",
            backgroundColor: "#3b82f6",
            color: "white",
            border: "none",
            borderRadius: "5px",
            cursor: "pointer",
          }}
        >
          Thử lại
        </button>
      </div>
    );
  }

  return (
    <div style={{ padding: "20px", fontFamily: "Arial, sans-serif" }}>
      <h1 style={{ color: "#1f2937", marginBottom: "30px" }}>
        🎯 Admin Dashboard - EVM Warranty System
      </h1>

      <div
        style={{
          display: "grid",
          gridTemplateColumns: "repeat(auto-fit, minmax(250px, 1fr))",
          gap: "20px",
          marginBottom: "30px",
        }}
      >
        {/* Users Card */}
        <div
          style={{
            backgroundColor: "#f8fafc",
            padding: "20px",
            borderRadius: "8px",
            border: "1px solid #e5e7eb",
            textAlign: "center",
          }}
        >
          <h3 style={{ color: "#3b82f6", margin: "0 0 10px 0" }}>
            👥 Tổng số người dùng
          </h3>
          <div
            style={{ fontSize: "2rem", fontWeight: "bold", color: "#1f2937" }}
          >
            {data?.total_users || 0}
          </div>
          <p style={{ color: "#6b7280", margin: "5px 0 0 0" }}>
            Trong hệ thống
          </p>
        </div>

        {/* Service Centers Card */}
        <div
          style={{
            backgroundColor: "#f0fdf4",
            padding: "20px",
            borderRadius: "8px",
            border: "1px solid #bbf7d0",
            textAlign: "center",
          }}
        >
          <h3 style={{ color: "#059669", margin: "0 0 10px 0" }}>
            🏢 Trung tâm dịch vụ
          </h3>
          <div
            style={{ fontSize: "2rem", fontWeight: "bold", color: "#1f2937" }}
          >
            {data?.total_service_centers || 0}
          </div>
          <p style={{ color: "#6b7280", margin: "5px 0 0 0" }}>
            Đang hoạt động
          </p>
        </div>

        {/* Warranties Card */}
        <div
          style={{
            backgroundColor: "#fef3f2",
            padding: "20px",
            borderRadius: "8px",
            border: "1px solid #fecaca",
            textAlign: "center",
          }}
        >
          <h3 style={{ color: "#dc2626", margin: "0 0 10px 0" }}>
            📋 Warranty Claims
          </h3>
          <div
            style={{ fontSize: "2rem", fontWeight: "bold", color: "#1f2937" }}
          >
            {data?.total_warranties || 0}
          </div>
          <p style={{ color: "#6b7280", margin: "5px 0 0 0" }}>
            Tổng số claims
          </p>
        </div>

        {/* Cost Card */}
        <div
          style={{
            backgroundColor: "#fffbeb",
            padding: "20px",
            borderRadius: "8px",
            border: "1px solid #fed7aa",
            textAlign: "center",
          }}
        >
          <h3 style={{ color: "#d97706", margin: "0 0 10px 0" }}>
            💰 Tổng chi phí
          </h3>
          <div
            style={{ fontSize: "2rem", fontWeight: "bold", color: "#1f2937" }}
          >
            {(data?.total_cost || 0).toLocaleString()} VND
          </div>
          <p style={{ color: "#6b7280", margin: "5px 0 0 0" }}>Đã chi trả</p>
        </div>
      </div>

      <div
        style={{
          backgroundColor: "#f8fafc",
          padding: "20px",
          borderRadius: "8px",
          border: "1px solid #e5e7eb",
          marginBottom: "20px",
        }}
      >
        <h3 style={{ margin: "0 0 15px 0" }}>🎯 Hệ thống hoạt động:</h3>
        <ul style={{ margin: "0", paddingLeft: "20px" }}>
          <li>
            ✅ Backend API: <code>http://localhost:8004</code>
          </li>
          <li>✅ Database: XAMPP MySQL</li>
          <li>✅ Authentication: admin/admin123</li>
          <li>✅ Frontend: React Dashboard</li>
        </ul>
      </div>

      <div style={{ textAlign: "center" }}>
        <button
          onClick={loadDashboardData}
          style={{
            padding: "12px 24px",
            backgroundColor: "#10b981",
            color: "white",
            border: "none",
            borderRadius: "6px",
            cursor: "pointer",
            fontSize: "16px",
          }}
        >
          🔄 Refresh Data
        </button>
      </div>
    </div>
  );
}
