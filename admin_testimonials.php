<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/* ========================================
   ADMIN TESTIMONIALS MANAGEMENT
   ======================================== */

// Pastikan user sudah login dan adalah staff
$user = require_login();
require_role_staff($user);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Testimoni</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #fbc02d;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .stats {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #f9f9f9;
        }
        .stat-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #fbc02d;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .filters {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .testimonials-list {
            padding: 20px;
        }
        .testimonial-item {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .testimonial-header {
            background: #f9f9f9;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .testimonial-info h3 {
            margin: 0;
            color: #333;
        }
        .testimonial-meta {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .testimonial-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .testimonial-content {
            padding: 15px;
        }
        .testimonial-rating {
            margin-bottom: 10px;
        }
        .star {
            color: #fbc02d;
        }
        .star.empty {
            color: #ddd;
        }
        .testimonial-text {
            color: #555;
            line-height: 1.5;
        }
        .testimonial-actions {
            padding: 15px;
            background: #f9f9f9;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-approve {
            background: #28a745;
            color: white;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Kelola Testimoni</h1>
            <p>Selamat datang, <?= htmlspecialchars($user['nama_lengkap']) ?></p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number" id="totalCount">-</div>
                <div class="stat-label">Total Testimoni</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="pendingCount">-</div>
                <div class="stat-label">Menunggu Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="approvedCount">-</div>
                <div class="stat-label">Disetujui</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="rejectedCount">-</div>
                <div class="stat-label">Ditolak</div>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Filter Status:</label>
                <select id="statusFilter" onchange="loadTestimonials()">
                    <option value="">Semua</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                
                <label>Sort By:</label>
                <select id="sortBy" onchange="loadTestimonials()">
                    <option value="created_at DESC">Terbaru</option>
                    <option value="created_at ASC">Terlama</option>
                    <option value="rating DESC">Rating Tertinggi</option>
                    <option value="rating ASC">Rating Terendah</option>
                </select>
            </div>
        </div>
        
        <div class="testimonials-list">
            <div id="loadingIndicator" class="loading">
                Memuat testimoni...
            </div>
            <div id="testimonialsContainer"></div>
        </div>
    </div>

    <script>
        let testimonials = [];
        
        async function loadTestimonials() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const container = document.getElementById('testimonialsContainer');
            
            loadingIndicator.style.display = 'block';
            container.innerHTML = '';
            
            try {
                const response = await fetch('../backend/testimonials.php?action=list');
                const data = await response.json();
                
                if (data.ok) {
                    testimonials = data.data;
                    updateStats();
                    renderTestimonials();
                } else {
                    container.innerHTML = `<div class="error">Error: ${data.error}</div>`;
                }
            } catch (error) {
                container.innerHTML = `<div class="error">Error loading testimonials: ${error.message}</div>`;
            } finally {
                loadingIndicator.style.display = 'none';
            }
        }
        
        function updateStats() {
            const total = testimonials.length;
            const pending = testimonials.filter(t => t.status === 'pending').length;
            const approved = testimonials.filter(t => t.status === 'approved').length;
            const rejected = testimonials.filter(t => t.status === 'rejected').length;
            
            document.getElementById('totalCount').textContent = total;
            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('approvedCount').textContent = approved;
            document.getElementById('rejectedCount').textContent = rejected;
        }
        
        function renderTestimonials() {
            const container = document.getElementById('testimonialsContainer');
            const statusFilter = document.getElementById('statusFilter').value;
            const sortBy = document.getElementById('sortBy').value;
            
            let filteredTestimonials = testimonials;
            
            // Filter by status
            if (statusFilter) {
                filteredTestimonials = filteredTestimonials.filter(t => t.status === statusFilter);
            }
            
            // Sort
            filteredTestimonials.sort((a, b) => {
                const [field, direction] = sortBy.split(' ');
                let aVal = a[field];
                let bVal = b[field];
                
                if (field === 'created_at') {
                    aVal = new Date(aVal);
                    bVal = new Date(bVal);
                }
                
                if (direction === 'DESC') {
                    return bVal > aVal ? 1 : -1;
                } else {
                    return aVal > bVal ? 1 : -1;
                }
            });
            
            container.innerHTML = '';
            
            if (filteredTestimonials.length === 0) {
                container.innerHTML = '<div class="loading">Tidak ada testimoni ditemukan.</div>';
                return;
            }
            
            filteredTestimonials.forEach(testimonial => {
                const item = createTestimonialItem(testimonial);
                container.appendChild(item);
            });
        }
        
        function createTestimonialItem(testimonial) {
            const item = document.createElement('div');
            item.className = 'testimonial-item';
            
            const ratingStars = generateStars(testimonial.rating);
            const statusClass = `status-${testimonial.status}`;
            const createdDate = new Date(testimonial.created_at).toLocaleDateString('id-ID');
            
            item.innerHTML = `
                <div class="testimonial-header">
                    <div class="testimonial-info">
                        <h3>${testimonial.nama}</h3>
                        <div class="testimonial-meta">
                            ${createdDate} | IP: ${testimonial.ip_address || 'N/A'}
                            ${testimonial.approved_by_name ? ` | Approved by: ${testimonial.approved_by_name}` : ''}
                        </div>
                    </div>
                    <div class="testimonial-status ${statusClass}">
                        ${testimonial.status.toUpperCase()}
                    </div>
                </div>
                <div class="testimonial-content">
                    <div class="testimonial-rating">
                        ${ratingStars}
                    </div>
                    <div class="testimonial-text">
                        "${testimonial.caption}"
                    </div>
                </div>
                <div class="testimonial-actions">
                    ${testimonial.status === 'pending' ? `
                        <button class="btn btn-approve" onclick="approveTestimonial(${testimonial.testimoni_id})">
                            Approve
                        </button>
                        <button class="btn btn-reject" onclick="rejectTestimonial(${testimonial.testimoni_id})">
                            Reject
                        </button>
                    ` : ''}
                </div>
            `;
            
            return item;
        }
        
        function generateStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                const className = i <= rating ? 'star' : 'star empty';
                stars += `<span class="${className}">★</span>`;
            }
            return stars;
        }
        
        async function approveTestimonial(testimoniId) {
            if (!confirm('Apakah Anda yakin ingin menyetujui testimoni ini?')) {
                return;
            }
            
            try {
                const response = await fetch('../backend/testimonials.php?action=approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ testimoni_id: testimoniId })
                });
                
                const data = await response.json();
                
                if (data.ok) {
                    alert('Testimoni berhasil disetujui!');
                    loadTestimonials();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        async function rejectTestimonial(testimoniId) {
            if (!confirm('Apakah Anda yakin ingin menolak testimoni ini?')) {
                return;
            }
            
            try {
                const response = await fetch('../backend/testimonials.php?action=reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ testimoni_id: testimoniId })
                });
                
                const data = await response.json();
                
                if (data.ok) {
                    alert('Testimoni ditolak!');
                    loadTestimonials();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        // Load testimonials on page load
        document.addEventListener('DOMContentLoaded', loadTestimonials);
    </script>
</body>
</html>
