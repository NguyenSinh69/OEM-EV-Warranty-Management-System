

<?php $__env->startSection('title', 'Welcome - OEM EV Warranty Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="text-center">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-bold text-gray-900 mb-8">
            <i class="fas fa-car-battery text-blue-600"></i>
            OEM EV Warranty Management System
        </h1>
        
        <p class="text-xl text-gray-600 mb-12">
            Hệ thống quản lý bảo hành xe điện với workflow trạng thái tự động
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-3xl text-blue-600 mb-4">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Tạo Claim Mới</h3>
                <p class="text-gray-600 mb-4">Tạo yêu cầu bảo hành mới với form validation đầy đủ</p>
                <a href="<?php echo e(route('claims.create')); ?>" 
                   class="inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Bắt đầu
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-3xl text-green-600 mb-4">
                    <i class="fas fa-list-alt"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Quản lý Claims</h3>
                <p class="text-gray-600 mb-4">Xem và quản lý tất cả yêu cầu bảo hành</p>
                <a href="<?php echo e(route('claims.index')); ?>" 
                   class="inline-block px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Xem danh sách
                </a>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-3xl text-purple-600 mb-4">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Thống kê</h3>
                <p class="text-gray-600 mb-4">Xem báo cáo và thống kê tổng quan</p>
                <a href="<?php echo e(route('dashboard')); ?>" 
                   class="inline-block px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    Xem dashboard
                </a>
            </div>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6">Workflow Trạng thái</h2>
            <div class="flex justify-center items-center space-x-4 text-sm">
                <div class="flex items-center">
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Đã gửi</span>
                    <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                </div>
                <div class="flex items-center">
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full">Đang xem xét</span>
                    <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                </div>
                <div class="flex items-center">
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full">Đã phê duyệt</span>
                    <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                </div>
                <div class="flex items-center">
                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full">Đang xử lý</span>
                    <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                </div>
                <div>
                    <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full">Hoàn thành</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\OEM-EV-Warranty-Management-System-main\Workflow\resources\views\welcome.blade.php ENDPATH**/ ?>