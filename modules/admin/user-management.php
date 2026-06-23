<?php
// modules/admin/user-management.php
$pageTitle = "Manajemen User & Role";
$activePage = "user-management";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

require_login();
if ($_SESSION['user_role'] !== 'SUPERADMIN') {
    header('Location: ' . base_url('modules/dashboard/dashboard.php'));
    exit;
}

$db = get_db_connection();
$tab = $_GET['tab'] ?? 'users';

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5f8a 100%); color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-users-cog me-2"></i>Manajemen User & Role</h2>
                    <p class="mb-0 opacity-75">Kelola user, role, dan hak akses pengguna</p>
                </div>
                <button class="btn btn-light fw-semibold" data-bs-toggle="modal" data-bs-target="#addUserModal" id="btnAddUser">
                    <i class="fas fa-user-plus me-2"></i>Tambah User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabs — styled cards, always visible -->
<div class="tab-cards mb-4">
    <a href="?tab=users" class="tab-card <?php echo $tab=='users'||$tab==''?'active':''; ?>">
        <span class="tab-icon" style="background:#eef2ff;color:#4f46e5"><i class="fas fa-users"></i></span>
        <span class="tab-label">Daftar User</span>
    </a>
    <a href="?tab=roles" class="tab-card <?php echo $tab=='roles'?'active':''; ?>">
        <span class="tab-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-user-tag"></i></span>
        <span class="tab-label">Manajemen Role</span>
    </a>
    <a href="?tab=access" class="tab-card <?php echo $tab=='access'?'active':''; ?>">
        <span class="tab-icon" style="background:#fce7f3;color:#db2777"><i class="fas fa-shield-alt"></i></span>
        <span class="tab-label">Manajemen Akses</span>
    </a>
</div>

<?php if ($tab === 'users' || $tab === ''): ?>
<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2 col-sm-6">
        <div class="dashboard-card text-center py-2 px-2"><i class="fas fa-users fa-2x text-primary mb-1 d-block"></i><h4 class="mb-0" id="totalUsers">0</h4><small class="text-muted">Total User</small></div>
    </div>
    <div class="col-md-4 col-lg-2 col-sm-6">
        <div class="dashboard-card text-center py-2 px-2"><i class="fas fa-user-graduate fa-2x text-success mb-1 d-block"></i><h4 class="mb-0" id="totalPendaftar">0</h4><small class="text-muted">Pendaftar</small></div>
    </div>
    <div class="col-md-4 col-lg-2 col-sm-6">
        <div class="dashboard-card text-center py-2 px-2"><i class="fas fa-user-check fa-2x text-warning mb-1 d-block"></i><h4 class="mb-0" id="totalVerifikator">0</h4><small class="text-muted">Admin Pusat</small></div>
    </div>
    <div class="col-md-4 col-lg-2 col-sm-6">
        <div class="dashboard-card text-center py-2 px-2"><i class="fas fa-building fa-2x mb-1 d-block" style="color:#7c3aed"></i><h4 class="mb-0" id="totalAdminSatker">0</h4><small class="text-muted">Admin Satker</small></div>
    </div>
    <div class="col-md-4 col-lg-2 col-sm-6">
        <div class="dashboard-card text-center py-2 px-2"><i class="fas fa-user-tie fa-2x text-info mb-1 d-block"></i><h4 class="mb-0" id="totalAsesor">0</h4><small class="text-muted">Asesor</small></div>
    </div>
    <div class="col-md-4 col-lg-2 col-sm-6">
        <div class="dashboard-card text-center py-2 px-2"><i class="fas fa-crown fa-2x text-danger mb-1 d-block"></i><h4 class="mb-0" id="totalSuperadmin">0</h4><small class="text-muted">Superadmin</small></div>
    </div>
</div>

<div class="dashboard-card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom"><h4 class="mb-0"><i class="fas fa-list me-2"></i>Daftar User</h4></div>
    <div class="card-body">
<div class="row mb-3">
    <div class="col-md-2"><select class="form-select" id="filterRole"><option value="">Semua Role</option><option value="USER">Pendaftar</option><option value="ADMIN_PUSAT">Admin Pusat</option><option value="ADMIN_SATKER">Admin Satker</option><option value="ASSESSOR">Asesor</option><option value="SUPERADMIN">Super Admin</option></select></div>
    <div class="col-md-2"><select class="form-select" id="filterStatus"><option value="">Semua Status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></div>
    <div class="col-md-2"><select class="form-select" id="filterEmailVerified"><option value="">Verifikasi Email</option><option value="verified">Sudah Diverifikasi</option><option value="not_verified">Belum Diverifikasi</option></select></div>
    <div class="col-md-6"><div class="input-group"><input type="text" class="form-control" id="searchUser" placeholder="Cari user..."><button class="btn btn-primary" id="btnSearch"><i class="fas fa-search"></i></button></div></div>
</div>
<div class="table-responsive"><table class="table table-hover" id="userTable"><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Email Verifikasi</th><th>Terdaftar</th><th>Aksi</th></tr></thead><tbody id="userTableBody"><tr><td colspan="7" class="text-center"><div class="spinner-border text-primary"></div></td></tr></tbody></table></div>
<nav><ul class="pagination justify-content-center" id="pagination"></ul></nav>
</div></div>

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
                                    <option value="ADMIN_PUSAT">Admin Pusat</option>
                                    <option value="ADMIN_SATKER">Admin Satker</option>
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
                                    <option value="ADMIN_PUSAT">Admin Pusat</option>
                                    <option value="ADMIN_SATKER">Admin Satker</option>
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

<?php endif; ?>

<!-- ===== STYLES — loaded on ALL tabs ===== -->
<style>/* Shared CSS in modules/assets/css/style.css */</style>

<?php if ($tab === 'roles'): ?>
<!-- ========== TAB: MANAJEMEN ROLE ========== -->
<div class="dashboard-card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-user-tag me-2 text-primary"></i>Daftar Role</h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th style="width:60px">ID</th><th style="width:180px">Kode Role</th><th>Nama Role</th><th>Deskripsi</th><th style="width:80px">Status</th></tr></thead>
            <tbody>
                <?php 
                $roles = $db->query("SELECT r.*, COUNT(ur.user_id) as total_users FROM roles r LEFT JOIN user_roles ur ON r.id = ur.role_id GROUP BY r.id ORDER BY r.id")->fetchAll();
                foreach ($roles as $r): 
                    $rc = ['SUPERADMIN'=>'#dc3545','ADMIN_PUSAT'=>'#0d6efd','ADMIN_SATKER'=>'#7c3aed','ASSESSOR'=>'#f59e0b','USER'=>'#6c757d'];
                    $color = $rc[$r['role_code']] ?? '#6c757d';
                ?>
                <tr>
                    <td><span class="text-muted">#<?php echo $r['id']; ?></span></td>
                    <td><span class="badge px-3 py-2 fw-bold" style="background:<?php echo $color; ?>;color:#fff"><?php echo htmlspecialchars($r['role_code']); ?></span></td>
                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($r['role_name']); ?></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($r['description'] ?? ''); ?></small></td>
                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?php echo (int)$r['total_users']; ?> user</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 rounded-3 mt-3" style="background:#f0f7ff">
        <small><i class="fas fa-info-circle me-1 text-primary"></i>Role dikelola melalui database. Untuk menambah role baru, gunakan query SQL: <code>INSERT INTO roles (role_code, role_name, description) VALUES ('NEW_ROLE', 'Role Name', 'Description');</code></small>
    </div>
</div>

<?php elseif ($tab === 'access'): ?>
<!-- ========== TAB: MANAJEMEN AKSES (EDITABLE) ========== -->
<div class="dashboard-card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Hak Akses per Role</h5>
        <button class="btn btn-primary btn-sm" id="btnSaveAccess" onclick="saveAccess()">
            <i class="fas fa-save me-1"></i>Simpan Perubahan
        </button>
    </div>
    <p class="text-muted mt-3">Centang fitur yang boleh diakses oleh masing-masing role. Klik <strong>Simpan Perubahan</strong> setelah selesai.</p>
    
    <div class="table-responsive">
        <table class="table table-bordered align-middle" id="accessTable">
            <thead class="table-light">
                <tr>
                    <th style="width:220px">Fitur / Akses</th>
                    <th class="text-center" style="width:130px">Superadmin</th>
                    <th class="text-center" style="width:130px">Admin Pusat</th>
                    <th class="text-center" style="width:130px">Admin Satker</th>
                    <th class="text-center" style="width:130px">Asesor</th>
                    <th class="text-center" style="width:130px">Pendaftar</th>
                </tr>
            </thead>
            <tbody id="accessTableBody">
                <tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Memuat data akses...</p></td></tr>
            </tbody>
        </table>
    </div>
    <div class="mt-2" id="accessStatus"></div>
</div>

<?php endif; ?>

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
        timeout: 10000,
        success: function(response) {
            console.log('Stats API Response:', response);
            if (response.success) {
                $('#totalUsers').text(response.data.total_users || 0);
                $('#totalPendaftar').text(response.data.total_pendaftar || 0);
                $('#totalVerifikator').text(response.data.total_verifikator || response.data.total_admin || 0);
                $('#totalAdminSatker').text(response.data.total_admin_satker || 0);
                $('#totalAsesor').text(response.data.total_asesor || 0);
                $('#totalSuperadmin').text(response.data.total_superadmin || 0);
                console.log('Stats updated successfully');
            } else {
                console.error('Server error loading stats:', response.message);
                showStatsError('Server error: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading stats - status:', status, 'error:', error);
            console.error('HTTP status:', xhr.status, 'Response:', xhr.responseText);
            
            var errorMsg = 'Gagal memuat statistik. ';
            if (xhr.status === 0) {
                errorMsg += 'Tidak dapat terhubung ke server. Periksa koneksi.';
            } else if (xhr.status === 403) {
                errorMsg += 'Akses ditolak. Silakan login ulang.';
            } else if (xhr.status === 500) {
                errorMsg += 'Server error (500). Cek response: ' + xhr.responseText.substring(0, 200);
            } else {
                errorMsg += 'HTTP ' + xhr.status + ': ' + error;
            }
            showStatsError(errorMsg);
        }
    });
}

function showStatsError(message) {
    // Tampilkan error tapi jangan pakai alert
    console.error('Stats Error:', message);
    $('#totalUsers').text('⚠️');
    $('#totalPendaftar').text('⚠️');
    $('#totalAdmin').text('⚠️');
    $('#totalAsesor').text('⚠️');
    
    // Tampilkan toast jika showToast tersedia
    if (typeof window.showToast === 'function') {
        window.showToast(message, 'danger');
    }
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
        timeout: 15000,
        success: function(response) {
            console.log('Users API Response:', response);
            
            if (response.success) {
                renderUsers(response.data.users);
                renderPagination(response.data.pagination);
                console.log('Users rendered successfully');
            } else {
                $('#userTableBody').html(
                    '<tr>' +
                    '<td colspan="7" class="text-center text-danger py-4">' +
                    '<i class="fas fa-exclamation-triangle fa-2x mb-3"></i>' +
                    '<p>Error: ' + escapeHtml(response.message || 'Unknown error') + '</p>' +
                    '</td>' +
                    '</tr>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error loading users - status:', status, 'error:', error);
            console.error('HTTP status:', xhr.status, 'Response:', xhr.responseText);
            
            var errorMsg = 'Gagal memuat data user. ';
            if (xhr.status === 0) {
                errorMsg += 'Tidak dapat terhubung ke server.';
            } else if (xhr.status === 403) {
                errorMsg += 'Akses ditolak. Silakan login ulang.';
            } else if (xhr.status === 500) {
                errorMsg += 'Server error. Detail: ' + (xhr.responseText ? xhr.responseText.substring(0, 150) : 'N/A');
            } else {
                errorMsg += 'HTTP ' + xhr.status + ': ' + error;
            }
            
            $('#userTableBody').html(
                '<tr>' +
                '<td colspan="7" class="text-center text-danger py-4">' +
                '<i class="fas fa-exclamation-triangle fa-2x mb-3"></i>' +
                '<p>' + escapeHtml(errorMsg) + '</p>' +
                '<button class="btn btn-outline-primary btn-sm mt-2" onclick="loadUsers()">' +
                '<i class="fas fa-redo me-1"></i>Coba Lagi</button>' +
                '</td>' +
                '</tr>'
            );
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
        case 'ADMIN_PUSAT': return 'badge-admin';
        case 'ADMIN_SATKER': return 'badge-admin-satker';
        case 'ASSESSOR': return 'badge-asesor';
        case 'SUPERADMIN': return 'badge-superadmin';
        default: return 'badge-secondary';
    }
}

function getRoleDisplay(roleCode) {
    switch(roleCode) {
        case 'USER': return 'Pendaftar';
        case 'ADMIN_PUSAT': return 'Admin Pusat';
        case 'ADMIN_SATKER': return 'Admin Satker';
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

// =============================================
// MANAJEMEN AKSES — Load & Save
// =============================================
var accessData = {};
var allRoles = ['SUPERADMIN','ADMIN_PUSAT','ADMIN_SATKER','ASSESSOR','USER'];
var allFeatures = [
    'Manajemen User','Manajemen Ujian','Master Data Ujian',
    'Manajemen Unit Kerja','Verifikasi Berkas','Lihat Pendaftar',
    'Input Nilai','Kelola Pengumuman','Lihat Pengumuman',
    'Sertifikat','Daftar Ujian','Edit Profil'
];
var defaultAccess = {
    SUPERADMIN: ['Manajemen User','Manajemen Ujian','Master Data Ujian','Manajemen Unit Kerja','Verifikasi Berkas','Lihat Pendaftar','Input Nilai','Kelola Pengumuman','Lihat Pengumuman','Sertifikat','Daftar Ujian','Edit Profil'],
    ADMIN_PUSAT: ['Verifikasi Berkas','Lihat Pendaftar','Lihat Pengumuman','Edit Profil'],
    ADMIN_SATKER: ['Verifikasi Berkas','Lihat Pendaftar','Lihat Pengumuman','Edit Profil'],
    ASSESSOR: ['Input Nilai','Lihat Pengumuman','Edit Profil'],
    USER: ['Lihat Pengumuman','Sertifikat','Daftar Ujian','Edit Profil']
};

// Load access from API
function loadAccess() {
    $.ajax({
        url: 'user-api.php?action=access',
        type: 'GET', dataType: 'json', timeout: 10000,
        success: function(resp) {
            if (resp.success && resp.data) { accessData = resp.data; }
            else { accessData = defaultAccess; }
            renderAccessTable();
        },
        error: function() { accessData = defaultAccess; renderAccessTable(); }
    });
}

function renderAccessTable() {
    var html = '';
    allFeatures.forEach(function(feature) {
        html += '<tr class="access-row"><td>' + escapeHtml(feature) + '</td>';
        allRoles.forEach(function(role) {
            var hasAccess = accessData[role] && accessData[role].indexOf(feature) !== -1;
            var disabled = (role === 'SUPERADMIN') ? ' disabled checked' : '';
            var checked = hasAccess || (role === 'SUPERADMIN') ? ' checked' : '';
            var color = role === 'SUPERADMIN' ? '#dc3545' : (role === 'ADMIN_PUSAT' ? '#0d6efd' : (role === 'ADMIN_SATKER' ? '#7c3aed' : (role === 'ASSESSOR' ? '#f59e0b' : '#6c757d')));
            html += '<td class="text-center">';
            html += '<input type="checkbox" class="form-check-input access-toggle" style="border-color:'+color+'" onchange="toggleAccess(\''+role+'\',\''+feature+'\',this.checked)"'+checked+disabled+'>';
            html += '</td>';
        });
        html += '</tr>';
    });
    $('#accessTableBody').html(html);
}

function toggleAccess(role, feature, enabled) {
    if (!accessData[role]) accessData[role] = [];
    if (enabled) {
        if (accessData[role].indexOf(feature) === -1) accessData[role].push(feature);
    } else {
        accessData[role] = accessData[role].filter(function(f) { return f !== feature; });
    }
}

function saveAccess() {
    var btn = $('#btnSaveAccess');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Menyimpan...');
    
    $.ajax({
        url: 'user-api.php?action=save_access',
        type: 'POST', dataType: 'json',
        data: { permissions: JSON.stringify(accessData) },
        timeout: 10000,
        success: function(resp) {
            if (resp.success) {
                $('#accessStatus').html('<div class="alert alert-success alert-dismissible fade show mb-0"><i class="fas fa-check-circle me-1"></i>'+resp.message+'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            } else {
                $('#accessStatus').html('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle me-1"></i>'+resp.message+'</div>');
            }
        },
        error: function() {
            $('#accessStatus').html('<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle me-1"></i>Gagal menyimpan. Coba lagi.</div>');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Simpan Perubahan');
        }
    });
}

// Load access when on access tab
if (window.location.search.indexOf('tab=access') !== -1) { loadAccess(); }
</script>