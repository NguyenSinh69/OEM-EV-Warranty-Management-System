@extends('layout')

@section('title', 'Tạo Warranty Claim Mới')

@section('content')
<div x-data="createClaimForm()" class="max-w-4xl mx-auto">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Tạo Yêu Cầu Bảo Hành Mới</h2>
        
        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Customer & Product Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Khách hàng <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.customer_id" 
                            :class="errors.customer_id ? 'border-red-500' : 'border-gray-300'"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn khách hàng --</option>
                        <template x-for="customer in customers" :key="customer.id">
                            <option :value="customer.id" x-text="`${customer.first_name} ${customer.last_name} (${customer.email})`"></option>
                        </template>
                    </select>
                    <p x-show="errors.customer_id" x-text="errors.customer_id" class="text-red-500 text-sm mt-1"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Sản phẩm <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.product_id"
                            :class="errors.product_id ? 'border-red-500' : 'border-gray-300'"
                            class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn sản phẩm --</option>
                        <template x-for="product in products" :key="product.id">
                            <option :value="product.id" x-text="`${product.brand} ${product.model} (VIN: ${product.vin})`"></option>
                        </template>
                    </select>
                    <p x-show="errors.product_id" x-text="errors.product_id" class="text-red-500 text-sm mt-1"></p>
                </div>
            </div>

            <!-- Claim Type & Priority -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Loại sự cố <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.claim_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="MANUFACTURING_DEFECT">Lỗi sản xuất</option>
                        <option value="NORMAL_WEAR">Hao mòn tự nhiên</option>
                        <option value="ACCIDENTAL_DAMAGE">Hư hỏng do tai nạn</option>
                        <option value="ELECTRICAL_ISSUE">Sự cố điện</option>
                        <option value="BATTERY_ISSUE">Sự cố pin</option>
                        <option value="SOFTWARE_ISSUE">Sự cố phần mềm</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Mức độ ưu tiên <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.priority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="LOW">Thấp</option>
                        <option value="MEDIUM">Trung bình</option>
                        <option value="HIGH">Cao</option>
                        <option value="CRITICAL">Khẩn cấp</option>
                    </select>
                </div>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tiêu đề <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="form.title"
                       :class="errors.title ? 'border-red-500' : 'border-gray-300'"
                       placeholder="Nhập tiêu đề ngắn gọn cho sự cố"
                       class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p x-show="errors.title" x-text="errors.title" class="text-red-500 text-sm mt-1"></p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Mô tả chi tiết <span class="text-red-500">*</span>
                </label>
                <textarea x-model="form.description" rows="5"
                          :class="errors.description ? 'border-red-500' : 'border-gray-300'"
                          placeholder="Mô tả chi tiết về sự cố, triệu chứng, và hoàn cảnh xảy ra..."
                          class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <p x-show="errors.description" x-text="errors.description" class="text-red-500 text-sm mt-1"></p>
            </div>

            <!-- Issue Date & Mileage -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ngày phát hiện sự cố <span class="text-red-500">*</span>
                    </label>
                    <input type="date" x-model="form.issue_date"
                           :class="errors.issue_date ? 'border-red-500' : 'border-gray-300'"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p x-show="errors.issue_date" x-text="errors.issue_date" class="text-red-500 text-sm mt-1"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Số km đã đi <span class="text-red-500">*</span>
                    </label>
                    <input type="number" x-model="form.reported_mileage" min="0"
                           :class="errors.reported_mileage ? 'border-red-500' : 'border-gray-300'"
                           placeholder="0"
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p x-show="errors.reported_mileage" x-text="errors.reported_mileage" class="text-red-500 text-sm mt-1"></p>
                </div>
            </div>

            <!-- File Attachments -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Đính kèm hình ảnh/video
                </label>
                <input type="file" multiple @change="handleFileChange($event)"
                       accept="image/jpeg,image/png,image/gif,application/pdf,video/mp4"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-sm text-gray-500 mt-1">
                    Chấp nhận: JPG, PNG, GIF, PDF, MP4 (tối đa 10MB/file)
                </p>
                <div x-show="selectedFiles.length > 0" class="mt-2">
                    <p class="text-sm font-medium text-gray-700">Đã chọn <span x-text="selectedFiles.length"></span> file:</p>
                    <template x-for="file in selectedFiles" :key="file.name">
                        <p class="text-sm text-gray-600">• <span x-text="file.name"></span> (<span x-text="formatFileSize(file.size)"></span>)</p>
                    </template>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('claims.index') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Hủy
                </a>
                <button type="submit" :disabled="loading"
                        :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span x-show="!loading">Tạo yêu cầu bảo hành</span>
                    <span x-show="loading">Đang xử lý...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function createClaimForm() {
    return {
        form: {
            customer_id: '',
            product_id: '',
            claim_type: 'MANUFACTURING_DEFECT',
            title: '',
            description: '',
            issue_date: '',
            reported_mileage: 0,
            priority: 'MEDIUM'
        },
        customers: [],
        products: [],
        selectedFiles: [],
        errors: {},
        loading: false,

        async init() {
            await this.loadCustomers();
            await this.loadProducts();
        },

        async loadCustomers() {
            try {
                const response = await fetch('/api/customers');
                const data = await response.json();
                if (data.success) {
                    this.customers = data.data;
                }
            } catch (error) {
                console.error('Error loading customers:', error);
            }
        },

        async loadProducts() {
            try {
                const response = await fetch('/api/products');
                const data = await response.json();
                if (data.success) {
                    this.products = data.data;
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        },

        handleFileChange(event) {
            this.selectedFiles = Array.from(event.target.files);
        },

        formatFileSize(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unitIndex = 0;
            
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }
            
            return `${size.toFixed(2)} ${units[unitIndex]}`;
        },

        async submitForm() {
            this.loading = true;
            this.errors = {};

            try {
                const formData = new FormData();
                
                // Add form fields
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });

                // Add files
                this.selectedFiles.forEach(file => {
                    formData.append('attachments[]', file);
                });

                const response = await fetch('/api/warranty-claims', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('Tạo yêu cầu bảo hành thành công!');
                    window.location.href = '/claims';
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        alert(data.message || 'Có lỗi xảy ra');
                    }
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('Có lỗi xảy ra khi gửi form');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection