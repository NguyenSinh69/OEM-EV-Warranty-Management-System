'use client';

import React, { createContext, useContext, useEffect, useState } from 'react';
import Cookies from 'js-cookie';
import { User } from '@/types';
import { api } from '@/lib/api';

interface AuthContextType {
  user: User | null;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => void;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = Cookies.get('auth_token');
    const userData = Cookies.get('user_data');
    
    if (token && userData) {
      try {
        setUser(JSON.parse(userData));
      } catch (error) {
        console.error('Error parsing user data:', error);
        Cookies.remove('auth_token');
        Cookies.remove('user_data');
      }
    }
    setLoading(false);
  }, []);

  const login = async (email: string, password: string): Promise<boolean> => {
    try {
      setLoading(true);
      
      // Mock authentication for demo
      let mockUser = null;
      
      // Admin account
      if (email === 'admin@evm.com' && password === 'admin123') {
        mockUser = {
          id: 1,
          name: 'Administrator',
          email: 'admin@evm.com',
          role: 'admin' as const
        };
      }
      // EVM Staff account
      else if (email === 'staff@evm.com' && password === 'staff123') {
        mockUser = {
          id: 2,
          name: 'EVM Staff',
          email: 'staff@evm.com',
          role: 'evm_staff' as const
        };
      }
      // SC Staff account
      else if (email === 'sc-staff@evm.com' && password === 'sc123') {
        mockUser = {
          id: 3,
          name: 'SC Staff',
          email: 'sc-staff@evm.com',
          role: 'sc_staff' as const,
          service_center_id: 1
        };
      }
      // Technician account
      else if (email === 'tech@evm.com' && password === 'tech123') {
        mockUser = {
          id: 4,
          name: 'Technician',
          email: 'tech@evm.com',
          role: 'technician' as const,
          service_center_id: 1
        };
      }
      // Customer account
      else if (email === 'nguyenvana@example.com' && password === 'password123') {
        mockUser = {
          id: 5,
          name: 'Nguyễn Văn A',
          email: 'nguyenvana@example.com',
          role: 'customer' as const
        };
      }
      
      if (mockUser) {
        // Store user data
        const token = `mock_token_${mockUser.id}_${Date.now()}`;
        Cookies.set('auth_token', token, { expires: 7 });
        Cookies.set('user_data', JSON.stringify(mockUser), { expires: 7 });
        setUser(mockUser);
        return true;
      }
      
      return false;
    } catch (error) {
      console.error('Login error:', error);
      return false;
    } finally {
      setLoading(false);
    }
  };

  const logout = () => {
    Cookies.remove('auth_token');
    Cookies.remove('user_data');
    setUser(null);
    api.logout();
    // Redirect to login page
    window.location.href = '/login';
  };

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}