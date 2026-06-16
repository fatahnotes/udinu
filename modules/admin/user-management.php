<?php
// modules/admin/user-management.php
$pageTitle = "Manajemen User";
$activePage = "user-management";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

// Hanya SUPERADMIN yang bisa mengakses
require_login();
if ($_SESSION['user_role'] !== 'SUPERADMIN') {
    header('Location: ' . base_url('modules/dashboard/dashboard.php'));
    exit;
}

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<!-- HANYA SATU KALI HTML -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Manajemen User</h2>
                <a href="#" class="btn-dashboard" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-2"></i>Tambah User
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1" id="totalUsers">0</h3>
                    <p class="text-muted mb-0">Total User</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-user-graduate fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1" id="totalPendaftar">0</h3>
                    <p class="text-muted mb-0">Pendaftar</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-user-check fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1" id="totalAdmin">0</h3>
                    <p class="text-muted mb-0">Admin</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-user-tie fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1" id="totalAsesor">0</h3>
                    <p class="text-muted mb-0">Asesor</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h4><i class="fas fa-list me-2"></i>Daftar User</h4>
    </div>
    
    <div class="card-body">
        <!-- Filter -->
<div class="row mb-3">
    <div class="col-md-2">
        <select class="form-select" id="filterRole">
            <option value="">Semua Role</option>
            <option value="USER">Pendaftar</option>
            <option value="ADMIN_VERIFIKATOR">Admin Verifikator</option>
            <option value="ASSESSOR">Asesor</option>
            <option value="SUPERADMIN">Super Admin</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filterStatus">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filterEmailVerified">
            <option value="">Verifikasi Email</option>
            <option value="verified">Sudah Diverifikasi</option>
            <option value="not_verified">Belum Diverifikasi</option>
        </select>
    </div>
    <div class="col-md-6">
        <div class="input-group">
            <input type="text" class="form-control" id="searchUser" placeholder="Cari user...">
            <button class="btn btn-primary" id="btnSearch">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
</div>
        
        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="userTable">
                <thead>
    <tr>
        <th>Nama</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Email Verifikasi</th>
        <th>Terdaftar</th>
        <th>Aksi</th>
    </tr>
</thead>
                <tbody id="userTableBody">
                    <!-- Data akan diisi via JavaScript -->
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center" id="pagination">
                <!-- Pagination akan diisi via JavaScript -->
            </ul>
        </nav>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="newUserName" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="newUserName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="newUserEmail" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="newUserEmail" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="newUserRole" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="newUserRole" name="role" required>
                                    <option value="">Pilih Role</option>
                                    <option value="USER">Pendaftar</option>
                                    <option value="ADMIN_VERIFIKATOR">Admin Verifikator</option>
                                    <option value="ASSESSOR">Asesor</option>
                                    <option value="SUPERADMIN">Super Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="newUserPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="newUserPassword" name="password" required>
                                <div class="form-text">Minimal 8 karakter</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" id="editUserId" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editUserName" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="editUserName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="editUserRole" class="form-label">Role</label>
                                <select class="form-select" id="editUserRole" name="role" required>
                                    <option value="USER">Pendaftar</option>
                                    <option value="ADMIN_VERIFIKATOR">Admin Verifikator</option>
                                    <option value="ASSESSOR">Asesor</option>
                                    <option value="SUPERADMIN">Super Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="editUserStatus" name="is_active">
                                <label class="form-check-label" for="editUserStatus">Aktif</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="editUserEmailVerified" name="is_email_verified">
                                <label class="form-check-label" for="editUserEmailVerified">Email Terverifikasi</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pagination {
    margin-top: 20px;
}

.table th {
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
}

.badge-role {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.badge-user {
    background-color: #6c757d;
    color: white;
}

.badge-admin {
    background-color: #0d6efd;
    color: white;
}

.badge-asesor {
    background-color: #ffc107;
    color: black;
}

.badge-superadmin {
    background-color: #dc3545;
    color: white;
}
</style>

<?php
// INCLUDE FOOTER SEBELUM JAVASCRIPT
include __DIR__ . '/../dashboard/footer-dashboard.php';
?>

<!-- JAVASCRIPT SETELAH FOOTER (SETELAH JQUERY DIMUAT) -->
<script>
$(document).ready(function() {
    console.log('User Management page loaded - jQuery is ready!');
    
    // Load user data
    loadUsers();
    loadStats();
    
    // Filter events
    $('#filterRole, #filterStatus').on('change', function() {
        console.log('Filter changed:', $(this).val());
        loadUsers();
    });
    
    $('#btnSearch').on('click', function() {
        console.log('Search clicked');
        loadUsers();
    });
    
    $('#searchUser').on('keypress', function(e) {
        if (e.which === 13) {
            console.log('Enter pressed in search');
            loadUsers();
        }
    });
    
    // Add user form
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Add user form submitted');
        addUser();
    });
    
    // Edit user form
    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Edit user form submitted');
        updateUser();
    });
    
    // Test API connection immediately
    console.log('Testing API connection...');
    $.ajax({
        url: 'user-api.php?action=stats',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('API Connection Test:', response);
        },
        error: function(xhr, status, error) {
            console.error('API Connection Error:', error);
        }
    });
});

function loadStats() {
    console.log('Loading stats via AJAX...');
    
    $.ajax({
        url: 'user-api.php?action=stats',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Stats API Response:', response);
            if (response.success) {
                $('#totalUsers').text(response.data.total_users);
                $('#totalPendaftar').text(response.data.total_pendaftar);
                $('#totalAdmin').text(response.data.total_admin);
                $('#totalAsesor').text(response.data.total_asesor);
                console.log('Stats updated successfully');
            } else {
                console.error('Error loading stats:', response.message);
                alert('Error loading stats: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading stats:', error, xhr.responseText);
            alert('Failed to load stats. Check console.');
        }
    });
}

function loadUsers(page = 1) {
    console.log('Loading users, page:', page);
    
    const params = {
        page: page,
        role: $('#filterRole').val(),
        status: $('#filterStatus').val(),
        email_verified: $('#filterEmailVerified').val(),
        search: $('#searchUser').val()
    };
    
    console.log('Request params:', params);
    
    // Show loading
    $('#userTableBody').html(`
        <tr>
            <td colspan="6" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading data...</p>
            </td>
        </tr>
    `);
    
    $.ajax({
        url: 'user-api.php?action=list&' + $.param(params),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Users API Response:', response);
            
            if (response.success) {
                renderUsers(response.data.users);
                renderPagination(response.data.pagination);
                console.log('Users rendered successfully');
            } else {
                $('#userTableBody').html(`
                    <tr>
                        <td colspan="6" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>Error: ${response.message}</p>
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading users:', error, xhr.responseText);
            $('#userTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <p>Failed to load data. Check console for details.</p>
                        <small>Error: ${error}</small>
                    </td>
                </tr>
            `);
        }
    });
}

function renderUsers(users) {
    console.log('Rendering users:', users);
    
    const tbody = $('#userTableBody');
    tbody.empty();
    
    if (!users || users.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-users fa-2x mb-3"></i>
                    <p>Tidak ada data user</p>
                </td>
            </tr>
        `);
        return;
    }
    
    users.forEach(function(user) {
        console.log('Processing user:', user);
        
        const userName = user.full_name || user.user_name || 'N/A';
        const userEmail = user.email || user.user_email || 'N/A';
        const userRole = user.role || 'USER';
        const isActive = user.is_active;
        const isEmailVerified = user.is_email_verified;
        const createdAt = user.created_at_formatted || formatDate(user.created_at) || '-';
        const userId = user.id;
        
        const roleClass = getRoleClass(userRole);
        const roleDisplay = getRoleDisplay(userRole);
        const statusBadge = isActive ? 
            '<span class="badge bg-success">Aktif</span>' : 
            '<span class="badge bg-danger">Nonaktif</span>';
        
        const emailVerifiedBadge = isEmailVerified ? 
            '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Terverifikasi</span>' : 
            '<span class="badge bg-warning"><i class="fas fa-exclamation-circle me-1"></i>Belum Diverifikasi</span>';
        
        const row = `
            <tr>
                <td>
                    <div class="fw-bold">${escapeHtml(userName)}</div>
                    <small class="text-muted">ID: ${userId}</small>
                </td>
                <td>${escapeHtml(userEmail)}</td>
                <td>
                    <span class="badge ${roleClass}">${roleDisplay}</span>
                </td>
                <td>${statusBadge}</td>
                <td>${emailVerifiedBadge}</td>
                <td>
                    <small>${createdAt}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary edit-btn" 
                                data-id="${userId}"
                                data-name="${escapeHtml(userName)}"
                                data-email="${escapeHtml(userEmail)}"
                                data-role="${userRole}"
                                data-active="${isActive}"
                                data-email-verified="${isEmailVerified}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-btn" data-id="${userId}">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn btn-outline-info reset-btn" data-id="${userId}">
                            <i class="fas fa-key"></i>
                        </button>
                        ${!isEmailVerified ? `
                        <button class="btn btn-outline-success verify-email-btn" data-id="${userId}" data-email="${escapeHtml(userEmail)}">
                            <i class="fas fa-envelope"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
    
    // Attach event handlers to new buttons
    attachEventHandlers();
}

function attachEventHandlers() {
    console.log('Attaching event handlers...');
    
    $('.edit-btn').off('click').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const role = $(this).data('role');
    const active = $(this).data('active');
    const emailVerified = $(this).data('email-verified');
    
    console.log('Edit button clicked:', {id, name, role, active, emailVerified});
    
    $('#editUserId').val(id);
    $('#editUserName').val(name);
    $('#editUserRole').val(role);
    $('#editUserStatus').prop('checked', active === true || active === 'true' || active === 1);
    $('#editUserEmailVerified').prop('checked', emailVerified === true || emailVerified === 'true' || emailVerified === 1);
    
    $('#editUserModal').modal('show');
});
    
    $('.delete-btn').off('click').on('click', function() {
        const id = $(this).data('id');
        console.log('Delete button clicked:', id);
        
        if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
            deleteUser(id);
        }
    });
    
    $('.reset-btn').off('click').on('click', function() {
        const id = $(this).data('id');
        console.log('Reset password button clicked:', id);
        
        if (confirm('Reset password user ini ke default?')) {
            resetPassword(id);
        }
    });
    
    $('.verify-email-btn').off('click').on('click', function() {
        const id = $(this).data('id');
        const email = $(this).data('email');
        console.log('Verify email button clicked:', id, email);
        
        if (confirm(`Verifikasi email untuk user: ${email}?\n\nSetelah diverifikasi, user dapat login tanpa perlu verifikasi email.`)) {
            verifyEmail(id);
        }
    });
}

function renderPagination(pagination) {
    console.log('Rendering pagination:', pagination);
    
    const paginationEl = $('#pagination');
    paginationEl.empty();
    
    if (!pagination || pagination.total_pages <= 1) {
        return;
    }
    
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    
    // Previous button
    if (currentPage > 1) {
        paginationEl.append(`
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}">
                    &laquo;
                </a>
            </li>
        `);
    } else {
        paginationEl.append(`
            <li class="page-item disabled">
                <span class="page-link">&laquo;</span>
            </li>
        `);
    }
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            if (i === currentPage) {
                paginationEl.append(`
                    <li class="page-item active">
                        <span class="page-link">${i}</span>
                    </li>
                `);
            } else {
                paginationEl.append(`
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }
        } else if (i === 2 && currentPage > 3) {
            paginationEl.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        } else if (i === totalPages - 1 && currentPage < totalPages - 2) {
            paginationEl.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }
    
    // Next button
    if (currentPage < totalPages) {
        paginationEl.append(`
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}">
                    &raquo;
                </a>
            </li>
        `);
    } else {
        paginationEl.append(`
            <li class="page-item disabled">
                <span class="page-link">&raquo;</span>
            </li>
        `);
    }
    
    // Attach click event to pagination links
    paginationEl.find('a.page-link[data-page]').on('click', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        console.log('Pagination clicked, page:', page);
        loadUsers(page);
    });
}

// Helper functions
function getRoleClass(roleCode) {
    switch(roleCode) {
        case 'USER': return 'badge-user';
        case 'ADMIN_VERIFIKATOR': return 'badge-admin';
        case 'ASSESSOR': return 'badge-asesor';
        case 'SUPERADMIN': return 'badge-superadmin';
        default: return 'badge-secondary';
    }
}

function getRoleDisplay(roleCode) {
    switch(roleCode) {
        case 'USER': return 'Pendaftar';
        case 'ADMIN_VERIFIKATOR': return 'Admin Verifikator';
        case 'ASSESSOR': return 'Asesor';
        case 'SUPERADMIN': return 'Super Admin';
        default: return roleCode;
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        console.error('Date formatting error:', e);
        return dateString;
    }
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function addUser() {
    const formData = $('#addUserForm').serialize();
    console.log('Adding user with data:', formData);
    
    $.ajax({
        url: 'user-api.php?action=create',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $('#addUserModal button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...');
        },
        success: function(response) {
            console.log('Add user response:', response);
            if (response.success) {
                alert('Success: ' + response.message);
                $('#addUserModal').modal('hide');
                $('#addUserForm')[0].reset();
                loadUsers();
                loadStats();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Add user error:', error, xhr.responseText);
            alert('Failed to add user. Check console.');
        },
        complete: function() {
            $('#addUserModal button[type="submit"]').prop('disabled', false).html('Simpan');
        }
    });
}

function updateUser() {
    const formData = $('#editUserForm').serialize();
    console.log('Updating user with data:', formData);
    
    $.ajax({
        url: 'user-api.php?action=update',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $('#editUserModal button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Menyimpan...');
        },
        success: function(response) {
            console.log('Update user response:', response);
            if (response.success) {
                alert('Success: ' + response.message);
                $('#editUserModal').modal('hide');
                loadUsers();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Update user error:', error, xhr.responseText);
            alert('Failed to update user. Check console.');
        },
        complete: function() {
            $('#editUserModal button[type="submit"]').prop('disabled', false).html('Simpan');
        }
    });
}

function deleteUser(id) {
    console.log('Deleting user ID:', id);
    
    $.ajax({
        url: 'user-api.php?action=delete',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            console.log('Delete response:', response);
            if (response.success) {
                alert('Success: ' + response.message);
                loadUsers();
                loadStats();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete error:', error, xhr.responseText);
            alert('Failed to delete user. Check console.');
        }
    });
}

function verifyEmail(id) {
    console.log('Verifying email for user ID:', id);
    
    $.ajax({
        url: 'user-api.php?action=verify-email',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            console.log('Verify email response:', response);
            if (response.success) {
                alert('Success: ' + response.message);
                loadUsers(); // Reload data
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Verify email error:', error, xhr.responseText);
            alert('Failed to verify email. Check console.');
        }
    });
}

function resetPassword(id) {
    console.log('Resetting password for user ID:', id);
    
    $.ajax({
        url: 'user-api.php?action=reset-password',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            console.log('Reset password response:', response);
            if (response.success) {
                alert('Success: ' + response.message);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Reset password error:', error, xhr.responseText);
            alert('Failed to reset password. Check console.');
        }
    });
}

// Make functions globally available for inline onclick handlers
window.loadUsers = loadUsers;
</script>