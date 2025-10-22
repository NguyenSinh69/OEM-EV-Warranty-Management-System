'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import toast, { Toaster } from 'react-hot-toast';

export default function LoginPage() {
  const [email, setEmail] = useState('admin@evm.com');
  const [password, setPassword] = useState('admin123');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const router = useRouter();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const success = await login(email, password);
      if (success) {
        toast.success('Đăng nhập thành công!');
        // Router sẽ tự động redirect based on role
        router.push('/');
      } else {
        toast.error('Email hoặc mật khẩu không đúng');
      }
    } catch (error) {
      toast.error('Đăng nhập thất bại');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <Toaster position="top-right" />
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            EVM Warranty System
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            Sign in to your account
          </p>
        </div>
        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          <div className="rounded-md shadow-sm -space-y-px">
            <div>
              <label htmlFor="email" className="sr-only">
                Email address
              </label>
              <input
                id="email"
                name="email"
                type="email"
                autoComplete="email"
                required
                className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                placeholder="Email address"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            <div>
              <label htmlFor="password" className="sr-only">
                Password
              </label>
              <input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                required
                className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
          </div>

          <div>
            <button
              type="submit"
              disabled={loading}
              className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {loading ? 'Signing in...' : 'Sign in'}
            </button>
          </div>

          <div className="mt-6">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-300" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-gray-50 text-gray-500">Demo Accounts</span>
              </div>
            </div>

            <div className="mt-6 grid grid-cols-1 gap-3">
              <button
                type="button"
                onClick={() => {
                  setEmail('admin@evm.com');
                  setPassword('admin123');
                }}
                className="w-full inline-flex justify-center py-2 px-4 border border-blue-300 rounded-md shadow-sm bg-blue-50 text-sm font-medium text-blue-700 hover:bg-blue-100"
              >
                👑 Admin Account
              </button>
              <button
                type="button"
                onClick={() => {
                  setEmail('staff@evm.com');
                  setPassword('staff123');
                }}
                className="w-full inline-flex justify-center py-2 px-4 border border-green-300 rounded-md shadow-sm bg-green-50 text-sm font-medium text-green-700 hover:bg-green-100"
              >
                🏭 EVM Staff Account
              </button>
              <button
                type="button"
                onClick={() => {
                  setEmail('sc-staff@evm.com');
                  setPassword('sc123');
                }}
                className="w-full inline-flex justify-center py-2 px-4 border border-purple-300 rounded-md shadow-sm bg-purple-50 text-sm font-medium text-purple-700 hover:bg-purple-100"
              >
                🏢 SC Staff Account
              </button>
              <button
                type="button"
                onClick={() => {
                  setEmail('tech@evm.com');
                  setPassword('tech123');
                }}
                className="w-full inline-flex justify-center py-2 px-4 border border-orange-300 rounded-md shadow-sm bg-orange-50 text-sm font-medium text-orange-700 hover:bg-orange-100"
              >
                🔧 Technician Account
              </button>
              <button
                type="button"
                onClick={() => {
                  setEmail('nguyenvana@example.com');
                  setPassword('password123');
                }}
                className="w-full inline-flex justify-center py-2 px-4 border border-indigo-300 rounded-md shadow-sm bg-indigo-50 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
              >
                👨‍👩‍👧‍👦 Customer Account
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}