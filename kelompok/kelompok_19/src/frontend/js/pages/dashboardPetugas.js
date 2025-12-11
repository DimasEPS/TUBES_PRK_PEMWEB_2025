// Dashboard Petugas JavaScript
// File: js/pages/dashboardPetugas.js

let chartInstance = null;

// Initialize dashboard on page load
document.addEventListener('DOMContentLoaded', function() {
    initDashboard();
});

// Initialize dashboard
async function initDashboard() {
    try {
        // Check authentication
        const isAuthenticated = await checkPetugasAuth();
        
        if (!isAuthenticated) {
            window.location.href = '../login.html';
            return;
        }

        // Load user info and dashboard data
        await Promise.all([
            loadUserInfo(),
            loadDashboard()
        ]);
    } catch (error) {
        console.error('Dashboard initialization error:', error);
        showError('Gagal memuat dashboard');
    }
}

// Check if user is authenticated as petugas
async function checkPetugasAuth() {
    try {
        const response = await apiGet('/petugas/dashboard');
        return response && response.success;
    } catch (error) {
        return false;
    }
}

// Load user information
async function loadUserInfo() {
    try {
        const response = await apiGet('/petugas/dashboard');
        
        if (response && response.success && response.data) {
            // Get user data from session (you might need a separate endpoint)
            // For now, we'll use placeholder
            const userName = 'Rina Wijaya'; // This should come from session
            const unitName = 'Unit Sarana & Prasarana'; // This should come from session
            
            document.getElementById('userName').textContent = userName;
            document.getElementById('welcomeName').textContent = `${userName} - ${unitName}`;
        }
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

// Load dashboard data
async function loadDashboard() {
    try {
        showLoading(true);
        
        const response = await apiGet('/petugas/dashboard');
        
        if (response && response.success) {
            displayDashboard(response.data);
        } else {
            showError('Gagal memuat data dashboard');
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showError('Terjadi kesalahan saat memuat dashboard');
    } finally {
        showLoading(false);
    }
}

// Display dashboard data
function displayDashboard(data) {
    const { stats, recent_complaints } = data;

    // Update statistics cards
    updateStats(stats);

    // Update recent activity list
    displayRecentActivity(recent_complaints);

    // Update chart
    updateCategoryChart(recent_complaints);

    // Show dashboard content
    document.getElementById('dashboardContent').style.display = 'block';
}

// Update statistics cards
function updateStats(stats) {
    document.getElementById('statTotal').textContent = stats.total || 0;
    document.getElementById('statMenunggu').textContent = stats.menunggu || 0;
    document.getElementById('statDiproses').textContent = stats.diproses || 0;
    document.getElementById('statSelesai').textContent = stats.selesai || 0;
}

// Display recent activity
function displayRecentActivity(complaints) {
    const activityList = document.getElementById('activityList');
    
    if (!complaints || complaints.length === 0) {
        activityList.innerHTML = `
            <p style="text-align:center;color:var(--gray-600);padding:2rem;">
                Tidak ada aktivitas terbaru
            </p>
        `;
        return;
    }

    const html = complaints.slice(0, 5).map(complaint => {
        const statusClass = complaint.status.toLowerCase();
        const statusLabel = getStatusLabel(complaint.status);
        const timeAgo = formatTimeAgo(complaint.created_at);

        return `
            <div class="activity-item blue">
                <div class="activity-title">${escapeHtml(complaint.title)}</div>
                <div class="activity-meta">
                    <span>${escapeHtml(complaint.mahasiswa_name)} - ${complaint.nim}</span>
                    <span>•</span>
                    <span>${timeAgo}</span>
                </div>
                <span class="activity-status status-${statusClass}">${statusLabel}</span>
            </div>
        `;
    }).join('');

    activityList.innerHTML = html;
}

// Update category chart
function updateCategoryChart(complaints) {
    if (!complaints || complaints.length === 0) {
        return;
    }

    // Group complaints by category
    const categoryCount = {};
    complaints.forEach(complaint => {
        const category = complaint.category_name;
        categoryCount[category] = (categoryCount[category] || 0) + 1;
    });

    // Sort by count and take top 4
    const sortedCategories = Object.entries(categoryCount)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 4);

    const labels = sortedCategories.map(item => item[0]);
    const values = sortedCategories.map(item => item[1]);

    // Create chart
    const ctx = document.getElementById('categoryChart');
    
    // Destroy previous chart if exists
    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Pengaduan',
                data: values,
                backgroundColor: [
                    '#1e40af',
                    '#3b82f6',
                    '#60a5fa',
                    '#93c5fd'
                ],
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    },
                    grid: {
                        display: true,
                        color: '#e5e7eb'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Handle logout
async function handleLogout() {
    if (!confirm('Yakin ingin keluar?')) {
        return;
    }

    try {
        await apiGet('/logout');
        window.location.href = '../login.html';
    } catch (error) {
        console.error('Logout error:', error);
        // Redirect anyway
        window.location.href = '../login.html';
    }
}

// Show/hide loading indicator
function showLoading(show) {
    const loader = document.getElementById('loadingIndicator');
    const content = document.getElementById('dashboardContent');
    
    if (show) {
        loader.style.display = 'block';
        content.style.display = 'none';
    } else {
        loader.style.display = 'none';
    }
}

// Show error message
function showError(message) {
    alert(message); // You can replace this with a better notification system
}

// Utility: Get status label
function getStatusLabel(status) {
    const statusMap = {
        'MENUNGGU': 'Menunggu',
        'DIPROSES': 'Diproses',
        'SELESAI': 'Selesai'
    };
    return statusMap[status] || status;
}

// Utility: Format time ago
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) {
        return 'Baru saja';
    }
    
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} menit lalu`;
    }
    
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} jam lalu`;
    }
    
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) {
        return `${diffInDays} hari lalu`;
    }
    
    // Format as date
    return date.toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short',
        year: 'numeric'
    });
}

// Utility: Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}