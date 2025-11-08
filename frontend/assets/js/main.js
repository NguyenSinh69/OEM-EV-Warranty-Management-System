// Main JavaScript file for EV Warranty Management System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize auto-hide alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Add fade-in animation to cards
    var cards = document.querySelectorAll('.card');
    cards.forEach(function(card, index) {
        card.style.animationDelay = (index * 0.1) + 's';
        card.classList.add('fade-in');
    });
});

// Utility Functions
class WarrantySystem {
    static showLoading(element) {
        if (element) {
            element.innerHTML = '<span class="loading"></span> Đang xử lý...';
            element.disabled = true;
        }
    }

    static hideLoading(element, originalText) {
        if (element) {
            element.innerHTML = originalText;
            element.disabled = false;
        }
    }

    static showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    static formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    static formatDate(date) {
        return new Date(date).toLocaleDateString('vi-VN');
    }

    static validateForm(formElement) {
        const inputs = formElement.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        return isValid;
    }

    static confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }
}

// Search and Filter Functions
class SearchFilter {
    static filterTable(searchInput, tableId) {
        const filter = searchInput.value.toUpperCase();
        const table = document.getElementById(tableId);
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            let found = false;
            const td = tr[i].getElementsByTagName('td');
            
            for (let j = 0; j < td.length; j++) {
                if (td[j]) {
                    const txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            tr[i].style.display = found ? '' : 'none';
        }
    }

    static filterCards(searchInput, cardContainer) {
        const filter = searchInput.value.toUpperCase();
        const cards = cardContainer.querySelectorAll('.card');

        cards.forEach(card => {
            const text = card.textContent || card.innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                card.style.display = '';
                card.classList.add('fade-in');
            } else {
                card.style.display = 'none';
            }
        });
    }
}

// AJAX Functions
class AjaxManager {
    static async makeRequest(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('AJAX Error:', error);
            WarrantySystem.showNotification('Có lỗi xảy ra khi kết nối đến server', 'danger');
            throw error;
        }
    }

    static async loadVehiclesByCustomer(customerId) {
        try {
            const vehicles = await this.makeRequest(`api/vehicles.php?customer_id=${customerId}`);
            return vehicles;
        } catch (error) {
            return [];
        }
    }

    static async updateWarrantyStatus(warrantyId, status, notes) {
        return await this.makeRequest('api/warranty-requests.php', 'PUT', {
            id: warrantyId,
            status: status,
            notes: notes
        });
    }
}

// Form Handlers
class FormHandlers {
    static initializeWarrantyForm() {
        const form = document.querySelector('#warrantyRequestForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!WarrantySystem.validateForm(form)) {
                WarrantySystem.showNotification('Vui lòng điền đầy đủ thông tin bắt buộc', 'warning');
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            WarrantySystem.showLoading(submitBtn);

            // Simulate form submission
            setTimeout(() => {
                WarrantySystem.hideLoading(submitBtn, originalText);
                WarrantySystem.showNotification('Yêu cầu bảo hành đã được gửi thành công!', 'success');
                form.reset();
                bootstrap.Modal.getInstance(form.closest('.modal')).hide();
            }, 2000);
        });
    }

    static initializeCustomerFilter() {
        const customerSelect = document.getElementById('customer_id');
        const vehicleSelect = document.getElementById('vehicle_registration_id');
        
        if (!customerSelect || !vehicleSelect) return;

        customerSelect.addEventListener('change', async function() {
            vehicleSelect.innerHTML = '<option value="">Đang tải...</option>';
            
            if (this.value) {
                try {
                    const vehicles = await AjaxManager.loadVehiclesByCustomer(this.value);
                    vehicleSelect.innerHTML = '<option value="">Chọn xe</option>';
                    
                    vehicles.forEach(vehicle => {
                        const option = document.createElement('option');
                        option.value = vehicle.id;
                        option.textContent = `${vehicle.name} - ${vehicle.license_plate}`;
                        vehicleSelect.appendChild(option);
                    });
                } catch (error) {
                    vehicleSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
                }
            } else {
                vehicleSelect.innerHTML = '<option value="">Chọn xe</option>';
            }
        });
    }
}

// Dashboard Functions
class Dashboard {
    static initializeCharts() {
        // Initialize Chart.js charts if element exists
        const chartElements = document.querySelectorAll('canvas[id$="Chart"]');
        
        chartElements.forEach(canvas => {
            if (window.Chart && !Chart.getChart(canvas)) {
                // Chart will be initialized by specific page scripts
                console.log(`Chart element found: ${canvas.id}`);
            }
        });
    }

    static updateStats() {
        // Update dashboard statistics
        const statsCards = document.querySelectorAll('.stats-card');
        
        statsCards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 200);
        });
    }

    static initializeRealTimeUpdates() {
        // Simulate real-time updates
        setInterval(() => {
            const timestampElements = document.querySelectorAll('[data-realtime="timestamp"]');
            timestampElements.forEach(element => {
                element.textContent = new Date().toLocaleString('vi-VN');
            });
        }, 60000); // Update every minute
    }
}

// Status Management
class StatusManager {
    static getStatusBadgeClass(status) {
        const statusClasses = {
            'pending': 'bg-warning',
            'in_review': 'bg-info',
            'approved': 'bg-success',
            'rejected': 'bg-danger',
            'in_progress': 'bg-primary',
            'completed': 'bg-success',
            'cancelled': 'bg-secondary'
        };
        return statusClasses[status] || 'bg-secondary';
    }

    static getStatusText(status) {
        const statusTexts = {
            'pending': 'Chờ xử lý',
            'in_review': 'Đang xem xét',
            'approved': 'Đã phê duyệt',
            'rejected': 'Từ chối',
            'in_progress': 'Đang xử lý',
            'completed': 'Hoàn thành',
            'cancelled': 'Đã hủy'
        };
        return statusTexts[status] || status;
    }

    static updateStatusBadges() {
        const badges = document.querySelectorAll('[data-status]');
        badges.forEach(badge => {
            const status = badge.getAttribute('data-status');
            badge.className = `badge ${this.getStatusBadgeClass(status)}`;
            badge.textContent = this.getStatusText(status);
        });
    }
}

// File Upload Handling
class FileManager {
    static initializeFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const files = Array.from(this.files);
                const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                const maxSize = 5 * 1024 * 1024; // 5MB

                files.forEach(file => {
                    if (!allowedTypes.includes(file.type)) {
                        WarrantySystem.showNotification(`File ${file.name} không được hỗ trợ`, 'warning');
                        return;
                    }

                    if (file.size > maxSize) {
                        WarrantySystem.showNotification(`File ${file.name} quá lớn (tối đa 5MB)`, 'warning');
                        return;
                    }
                });
            });
        });
    }

    static previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
}

// Export Functions
class ExportManager {
    static exportToExcel(tableId, filename = 'export.xlsx') {
        // This would integrate with a library like SheetJS
        WarrantySystem.showNotification('Chức năng xuất Excel sẽ được triển khai', 'info');
    }

    static exportToPDF(containerId, filename = 'export.pdf') {
        // This would integrate with a library like jsPDF
        WarrantySystem.showNotification('Chức năng xuất PDF sẽ được triển khai', 'info');
    }

    static printPage() {
        window.print();
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    FormHandlers.initializeWarrantyForm();
    FormHandlers.initializeCustomerFilter();
    Dashboard.initializeCharts();
    Dashboard.updateStats();
    Dashboard.initializeRealTimeUpdates();
    StatusManager.updateStatusBadges();
    FileManager.initializeFileUploads();

    // Add smooth scrolling to all anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Initialize search functionality
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const targetTable = this.getAttribute('data-search');
            if (targetTable) {
                SearchFilter.filterTable(this, targetTable);
            }
        });
    });

    // Add loading state to all form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('no-loading')) {
                WarrantySystem.showLoading(submitBtn);
            }
        });
    });
});

// Global functions for inline event handlers
window.WarrantySystem = WarrantySystem;
window.AjaxManager = AjaxManager;
window.StatusManager = StatusManager;
window.ExportManager = ExportManager;

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // Don't show errors to users in production
    if (window.location.hostname !== 'localhost') {
        WarrantySystem.showNotification('Đã xảy ra lỗi không mong muốn', 'danger');
    }
});

// Service Worker registration (for future PWA features)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        // navigator.serviceWorker.register('/sw.js');
    });
}