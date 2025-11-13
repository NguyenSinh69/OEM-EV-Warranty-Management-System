<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OEM EV Warranty Management System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-car-battery text-blue-600 mr-2"></i>
                        OEM EV Warranty
                    </h1>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('dashboard') }}" 
                       class="px-4 py-2 text-gray-700 hover:text-blue-600 {{ request()->routeIs('dashboard') ? 'text-blue-600 font-medium' : '' }}">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="{{ route('claims.index') }}" 
                       class="px-4 py-2 text-gray-700 hover:text-blue-600 {{ request()->routeIs('claims.*') ? 'text-blue-600 font-medium' : '' }}">
                        <i class="fas fa-clipboard-list mr-1"></i> Warranty Claims
                    </a>
                    <a href="{{ route('claims.create') }}" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i> Tạo Claim Mới
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-4 px-4">
            <p class="text-center text-gray-500 text-sm">
                © 2024 OEM EV Warranty Management System. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // CSRF Token setup for AJAX requests
        window.axios = {
            defaults: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }
        };
    </script>
</body>
</html>