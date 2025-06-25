/**
 * Dashboard JavaScript Functions
 * Handles real-time updates, AJAX calls, and interactive features
 */

class PortMonitoringDashboard {
    constructor() {
        this.refreshInterval = 300000; // 5 minutes
        this.charts = {};
        this.autoRefreshEnabled = true;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadInitialData();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        // Refresh button
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshAllData());
        }

        // Navigation smooth scrolling
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

        // Auto-refresh toggle
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshAllData();
            }
        });
    }

    async loadInitialData() {
        await this.loadDashboardStats();
        await this.loadCharts();
        this.updateLastRefreshTime();
    }

    async loadDashboardStats() {
        try {
            const response = await fetch('api.php?action=dashboard_stats');
            const data = await response.json();

            if (data.success) {
                this.updateStatsCards(data.data);
            } else {
                this.showError('Failed to load dashboard statistics');
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
            this.showError('Network error while loading statistics');
        }
    }

    updateStatsCards(stats) {
        const cards = {
            'device_count': stats.device_count,
            'port_count': stats.port_count,
            'error_count': stats.error_count,
            'total_records': stats.total_records
        };

        Object.entries(cards).forEach(([key, value]) => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                this.animateCounter(element, value);
            }
        });
    }

    animateCounter(element, targetValue) {
        const currentValue = parseInt(element.textContent) || 0;
        const increment = Math.ceil((targetValue - currentValue) / 20);

        if (currentValue === targetValue) return;

        let current = currentValue;
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= targetValue) ||
                (increment < 0 && current <= targetValue)) {
                current = targetValue;
                clearInterval(timer);
            }
            element.textContent = current.toLocaleString();
        }, 50);
    }

    async loadCharts() {
        await Promise.all([
            this.loadDeviceStatusChart(),
            this.loadErrorTrendChart()
        ]);
    }

    async loadDeviceStatusChart() {
        try {
            const response = await fetch('api.php?action=device_list');
            const data = await response.json();

            if (data.success) {
                const statusCounts = {
                    online: 0,
                    warning: 0,
                    offline: 0
                };

                data.data.forEach(device => {
                    statusCounts[device.status] = (statusCounts[device.status] || 0) + 1;
                });

                this.renderDeviceStatusChart(statusCounts);
            }
        } catch (error) {
            console.error('Error loading device status chart:', error);
        }
    }

    renderDeviceStatusChart(statusCounts) {
        const ctx = document.getElementById('deviceStatusChart');
        if (!ctx) return;

        if (this.charts.deviceStatus) {
            this.charts.deviceStatus.destroy();
        }

        this.charts.deviceStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Online', 'Warning', 'Offline'],
                datasets: [{
                    data: [statusCounts.online, statusCounts.warning, statusCounts.offline],
                    backgroundColor: ['#27ae60', '#f39c12', '#e74c3c'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    async loadErrorTrendChart() {
        try {
            const response = await fetch('api.php?action=error_trends&hours=24');
            const data = await response.json();

            if (data.success) {
                this.renderErrorTrendChart(data.data);
            }
        } catch (error) {
            console.error('Error loading error trend chart:', error);
        }
    }

    renderErrorTrendChart(trendData) {
        const ctx = document.getElementById('errorTrendChart');
        if (!ctx) return;

        if (this.charts.errorTrend) {
            this.charts.errorTrend.destroy();
        }

        const labels = trendData.length > 0 ?
            trendData.map(item => new Date(item.hour).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })) :
            ['No Data'];

        const errorData = trendData.length > 0 ?
            trendData.map(item => item.error_records) :
            [0];

        this.charts.errorTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Error Records',
                    data: errorData,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    async refreshDeviceList() {
        try {
            const response = await fetch('api.php?action=device_list');
            const data = await response.json();

            if (data.success) {
                this.updateDeviceTable(data.data);
            }
        } catch (error) {
            console.error('Error refreshing device list:', error);
        }
    }

    updateDeviceTable(devices) {
        const tbody = document.querySelector('#devices tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        devices.forEach(device => {
            const row = this.createDeviceRow(device);
            tbody.appendChild(row);
        });
    }

    createDeviceRow(device) {
        const row = document.createElement('tr');

        const statusInfo = this.getStatusInfo(device.status);
        const lastSeen = device.last_seen ?
            new Date(device.last_seen).toLocaleString() :
            'Never';

        row.innerHTML = `
            <td>
                <span class="status-indicator ${statusInfo.class}"></span>
                ${statusInfo.text}
            </td>
            <td>${this.escapeHtml(device.deviceid)}</td>
            <td>${this.escapeHtml(device.device || 'N/A')}</td>
            <td>${this.escapeHtml(device.ipaddress)}</td>
            <td>${this.escapeHtml(device.type || 'Unknown')}</td>
            <td><span class="badge bg-info">${device.port_count}</span></td>
            <td>${lastSeen}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="viewDevice(${device.deviceid})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="testConnection('${device.ipaddress}')">
                    <i class="fas fa-network-wired"></i>
                </button>
            </td>
        `;

        return row;
    }

    getStatusInfo(status) {
        const statusMap = {
            'online': { class: 'status-online', text: 'Online' },
            'warning': { class: 'status-warning', text: 'Warning' },
            'offline': { class: 'status-error', text: 'Offline' },
            'never_seen': { class: 'status-error', text: 'Never seen' }
        };

        return statusMap[status] || { class: 'status-error', text: 'Unknown' };
    }

    async refreshAllData() {
        this.showLoadingIndicator();

        try {
            await Promise.all([
                this.loadDashboardStats(),
                this.loadCharts(),
                this.refreshDeviceList()
            ]);

            this.updateLastRefreshTime();
            this.showSuccessMessage('Data refreshed successfully');
        } catch (error) {
            console.error('Error refreshing data:', error);
            this.showError('Failed to refresh data');
        } finally {
            this.hideLoadingIndicator();
        }
    }

    startAutoRefresh() {
        if (this.autoRefreshEnabled) {
            setInterval(() => {
                this.refreshAllData();
            }, this.refreshInterval);
        }
    }

    updateLastRefreshTime() {
        const timeElement = document.querySelector('.last-updated');
        if (timeElement) {
            timeElement.textContent = `Last updated: ${new Date().toLocaleString()}`;
        }
    }

    showLoadingIndicator() {
        const refreshBtn = document.querySelector('.refresh-btn i');
        if (refreshBtn) {
            refreshBtn.classList.add('fa-spin');
        }
    }

    hideLoadingIndicator() {
        const refreshBtn = document.querySelector('.refresh-btn i');
        if (refreshBtn) {
            refreshBtn.classList.remove('fa-spin');
        }
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }

    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'success'} border-0`;
        toast.setAttribute('role', 'alert');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Auto remove after hide
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
    }
}

// Global functions for button events
function viewDevice(deviceId) {
    window.location.href = `device-details.php?id=${deviceId}`;
}

function testConnection(ipAddress) {
    // This would typically make an AJAX call to test connectivity
    alert(`Testing connection to: ${ipAddress}\n(This would perform an actual connectivity test)`);
}

function refreshData() {
    if (window.dashboard) {
        window.dashboard.refreshAllData();
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    window.dashboard = new PortMonitoringDashboard();
});

// Handle page visibility changes to pause/resume auto-refresh
document.addEventListener('visibilitychange', function () {
    if (window.dashboard) {
        window.dashboard.autoRefreshEnabled = !document.hidden;
    }
}); 