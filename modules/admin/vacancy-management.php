<?php
// modules/admin/vacancy-management.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = "Manajemen Lowongan";
$activePage = "vacancy-management";
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

require_once __DIR__ . '/functions-vacancy.php';

$db = get_db_connection();
$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Debug: cek data yang diterima
error_log("=== VACANCY MANAGEMENT DEBUG ===");
error_log("Action: $action");
error_log("ID: $id");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST Data: " . print_r($_POST, true));
    
    if (isset($_POST['save_vacancy'])) {
        $data = [
            'vacancy_type_id' => intval($_POST['vacancy_type_id']),
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description']),
            'tahun_angkatan' => intval($_POST['tahun_angkatan']),
            'open_date' => $_POST['open_date'],
            'close_date' => $_POST['close_date'],
            'max_applicants' => isset($_POST['max_applicants']) && $_POST['max_applicants'] !== '' ? intval($_POST['max_applicants']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validasi
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Judul lowongan harus diisi';
        }
        
        if (empty($data['vacancy_type_id'])) {
            $errors[] = 'Jenis lowongan harus dipilih';
        } else {
            // Cek apakah jenis lowongan valid
            $vacancy_type = get_vacancy_type_by_id($db, $data['vacancy_type_id']);
            if (!$vacancy_type) {
                $errors[] = 'Jenis lowongan tidak valid';
            }
        }
        
        if ($data['open_date'] >= $data['close_date']) {
            $errors[] = 'Tanggal tutup harus setelah tanggal buka';
        }
        
        if ($data['tahun_angkatan'] < date('Y')) {
            $errors[] = 'Tahun angkatan tidak valid';
        }
        
        // Kumpulkan data formasi
        $formations = [];
        if (isset($_POST['formations']) && is_array($_POST['formations'])) {
            foreach ($_POST['formations'] as $index => $formation) {
                if (!empty($formation['name']) && !empty($formation['jumlah']) && $formation['jumlah'] > 0) {
                    $formations[] = [
                        'type' => $formation['type'] ?? 'umum',
                        'name' => trim($formation['name']),
                        'jumlah' => intval($formation['jumlah'])
                    ];
                }
            }
        }
        
        error_log("Formations data: " . print_r($formations, true));
        
        if (empty($errors)) {
            if (isset($_POST['vacancy_id']) && $_POST['vacancy_id'] > 0) {
                // Update
                $result = update_vacancy($db, $_POST['vacancy_id'], $data, $_SESSION['user_id'], $formations);
                if ($result) {
                    $success = 'Lowongan berhasil diperbarui';
                    $action = 'list';
                } else {
                    $error = 'Gagal memperbarui lowongan. Silakan coba lagi.';
                }
            } else {
                // Create
                $vacancy_id = create_vacancy($db, $data, $_SESSION['user_id'], $formations);
                if ($vacancy_id) {
                    $success = 'Lowongan berhasil dibuat';
                    $action = 'list';
                } else {
                    $error = 'Gagal membuat lowongan. Pastikan data jenis lowongan valid dan cek log error.';
                }
            }
        } else {
            $error = implode('<br>', $errors);
        }
    } elseif (isset($_POST['delete_vacancy'])) {
        $result = delete_vacancy($db, $_POST['vacancy_id'], $_SESSION['user_id']);
        if ($result) {
            $success = 'Lowongan berhasil dihapus';
        } else {
            $error = 'Gagal menghapus lowongan';
        }
        $action = 'list';
    }
}

// Ambil data berdasarkan action
if ($action === 'edit' && $id > 0) {
    $vacancy = get_vacancy_details($db, $id);
    if (!$vacancy) {
        $error = 'Lowongan tidak ditemukan';
        $action = 'list';
    }
} elseif ($action === 'view' && $id > 0) {
    $vacancy = get_vacancy_details($db, $id);
    if (!$vacancy) {
        $error = 'Lowongan tidak ditemukan';
        $action = 'list';
    }
}

// Ambil daftar jenis lowongan untuk dropdown
$vacancy_types = get_vacancy_types($db);
error_log("Vacancy types count: " . count($vacancy_types));

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Manajemen Lowongan</h2>
                <a href="?action=add" class="btn-dashboard">
                    <i class="fas fa-plus me-2"></i>Tambah Lowongan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($error): ?>
    <div class="alert alert-danger dashboard-alert alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success dashboard-alert alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'list' || $action === ''): ?>
    <!-- List Lowongan -->
    <div class="dashboard-card">
        <div class="card-header">
            <h4><i class="fas fa-list me-2"></i>Daftar Lowongan</h4>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Judul</th>
                        <th>Jenis</th>
                        <th>Tahun</th>
                        <th>Periode</th>
                        <th>Pendaftar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $vacancies = get_all_vacancies($db);
                    if (empty($vacancies)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-3"></i><br>
                                Belum ada lowongan yang dibuat
                            </td>
                        </tr>
                    <?php else:
                    foreach ($vacancies as $vac): 
                        $status_class = $vac['is_active'] ? 'success' : 'secondary';
                        $status_text = $vac['is_active'] ? 'Aktif' : 'Nonaktif';
                        
                        // Check if open
                        $today = date('Y-m-d');
                        $open_class = ($today >= $vac['open_date'] && $today <= $vac['close_date']) ? 'info' : 'warning';
                        $open_text = ($today >= $vac['open_date'] && $today <= $vac['close_date']) ? 'Buka' : 'Tutup';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($vac['vacancy_code']); ?></strong></td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($vac['title']); ?></div>
                            <small class="text-muted"><?php echo substr(htmlspecialchars($vac['description']), 0, 100); ?>...</small>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($vac['type_name']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($vac['tahun_angkatan']); ?></td>
                        <td>
                            <small><?php echo date('d/m/Y', strtotime($vac['open_date'])); ?></small><br>
                            <small><?php echo date('d/m/Y', strtotime($vac['close_date'])); ?></small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary"><?php echo $vac['total_applicants']; ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span><br>
                            <span class="badge bg-<?php echo $open_class; ?>"><?php echo $open_text; ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="?action=view&id=<?php echo $vac['id']; ?>" class="btn btn-outline-primary" title="Lihat">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?action=edit&id=<?php echo $vac['id']; ?>" class="btn btn-outline-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger delete-btn" 
                                        data-id="<?php echo $vac['id']; ?>" 
                                        data-title="<?php echo htmlspecialchars($vac['title']); ?>"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus lowongan <strong id="deleteTitle"></strong>?</p>
                    <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan.</small></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="vacancy_id" id="deleteId">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="delete_vacancy" value="1">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Form Lowongan -->
    <div class="dashboard-card">
        <div class="card-header">
            <h4>
                <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?> me-2"></i>
                <?php echo $action === 'add' ? 'Tambah Lowongan Baru' : 'Edit Lowongan'; ?>
            </h4>
        </div>
        
        <form method="POST" action="" id="vacancyForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="save_vacancy" value="1">
            <?php if (isset($vacancy['id'])): ?>
                <input type="hidden" name="vacancy_id" value="<?php echo $vacancy['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Jenis Lowongan -->
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="vacancy_type_id" class="form-label">Jenis Lowongan <span class="text-danger">*</span></label>
                        <select class="form-select" id="vacancy_type_id" name="vacancy_type_id" required 
                            <?php echo isset($vacancy['id']) ? 'disabled' : ''; ?>>
                            <option value="">Pilih Jenis Lowongan</option>
                            <?php foreach ($vacancy_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"
                                        data-type-code="<?php echo htmlspecialchars($type['type_code']); ?>"
                                    <?php echo (isset($vacancy['vacancy_type_id']) && $vacancy['vacancy_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($vacancy['id'])): ?>
                            <input type="hidden" name="vacancy_type_id" value="<?php echo $vacancy['vacancy_type_id']; ?>">
                            <div class="form-text">Jenis lowongan tidak dapat diubah setelah dibuat</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tahun Angkatan -->
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="tahun_angkatan" class="form-label">Tahun Angkatan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="tahun_angkatan" name="tahun_angkatan" 
                               min="<?php echo date('Y'); ?>" max="<?php echo date('Y') + 5; ?>"
                               value="<?php echo isset($vacancy['tahun_angkatan']) ? $vacancy['tahun_angkatan'] : date('Y'); ?>" required>
                    </div>
                </div>
                
                <!-- Judul -->
                <div class="col-12">
                    <div class="form-group mb-3">
                        <label for="title" class="form-label">Judul Lowongan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="Contoh: Seleksi Kepala Sekolah Garuda 2025"
                               value="<?php echo isset($vacancy['title']) ? htmlspecialchars($vacancy['title']) : ''; ?>" required>
                    </div>
                </div>
                
                <!-- Deskripsi -->
                <div class="col-12">
                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Deskripsi Lowongan</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Deskripsi lengkap tentang lowongan ini..."><?php echo isset($vacancy['description']) ? htmlspecialchars($vacancy['description']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Tanggal Buka -->
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="open_date" class="form-label">Tanggal Buka <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="open_date" name="open_date" 
                               value="<?php echo isset($vacancy['open_date']) ? $vacancy['open_date'] : date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <!-- Tanggal Tutup -->
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="close_date" class="form-label">Tanggal Tutup <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="close_date" name="close_date" 
                               value="<?php echo isset($vacancy['close_date']) ? $vacancy['close_date'] : date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                </div>
                
                <!-- Maksimal Pendaftar -->
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="max_applicants" class="form-label">Maksimal Pendaftar (Opsional)</label>
                        <input type="number" class="form-control" id="max_applicants" name="max_applicants" 
                               min="1" 
                               value="<?php echo isset($vacancy['max_applicants']) ? $vacancy['max_applicants'] : ''; ?>"
                               placeholder="Kosongkan untuk tidak terbatas">
                        <div class="form-text">Biarkan kosong jika tidak ada batasan</div>
                    </div>
                </div>
                
                <!-- Status Aktif -->
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   value="1" <?php echo (!isset($vacancy['is_active']) || $vacancy['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                        <div class="form-text">Nonaktifkan untuk menyembunyikan dari pendaftar</div>
                    </div>
                </div>
                
                <!-- Formasi Section -->
                <div class="col-12">
                    <div class="dashboard-card mb-3" id="formasi-section" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Formasi Lowongan
                                <small class="text-muted fs-6 ms-2">(Akan ditampilkan sesuai jenis lowongan)</small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="formasi-container">
                                <!-- Formasi akan ditambahkan dinamis di sini -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-3" id="add-formasi-btn">
                                <i class="fas fa-plus me-1"></i>Tambah Formasi
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="col-12">
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn-dashboard">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                        <a href="?" class="btn-outline-dashboard btn">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($action === 'view'): ?>
    <!-- View Lowongan -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-eye me-2"></i>Detail Lowongan
                </h4>
                <div>
                    <a href="?action=edit&id=<?php echo $vacancy['id']; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Informasi Utama -->
                <div class="mb-4">
                    <h5><?php echo htmlspecialchars($vacancy['title']); ?></h5>
                    <div class="d-flex gap-3 mb-3">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($vacancy['type_name']); ?></span>
                        <span class="badge bg-info">Tahun <?php echo htmlspecialchars($vacancy['tahun_angkatan']); ?></span>
                        <span class="badge bg-<?php echo $vacancy['is_active'] ? 'success' : 'secondary'; ?>">
                            <?php echo $vacancy['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Deskripsi</h6>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($vacancy['description'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Periode Pendaftaran -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title text-muted">Tanggal Buka</h6>
                                <h4 class="text-primary"><?php echo date('d M Y', strtotime($vacancy['open_date'])); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title text-muted">Tanggal Tutup</h6>
                                <h4 class="text-primary"><?php echo date('d M Y', strtotime($vacancy['close_date'])); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Formasi -->
                <?php if (!empty($vacancy['formations'])): ?>
                <div class="mb-4">
                    <h5>Formasi Lowongan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="60%">Nama Formasi</th>
                                    <th width="20%">Jenis</th>
                                    <th width="15%">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($vacancy['formations'] as $formasi): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($formasi['formation_name']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($formasi['formation_type']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo $formasi['jumlah']; ?> orang</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Persyaratan -->
                <div class="mb-4">
                    <h5>Persyaratan</h5>
                    
                    <div class="accordion" id="requirementsAccordion">
                        <!-- Persyaratan Umum -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#umumCollapse">
                                    <i class="fas fa-clipboard-list me-2"></i>Persyaratan Umum
                                </button>
                            </h2>
                            <div id="umumCollapse" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <ul class="list-group">
                                        <?php foreach ($vacancy['requirements'] as $req): 
                                            if ($req['requirement_type'] === 'umum'): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <span><?php echo htmlspecialchars($req['requirement_text']); ?></span>
                                                <small class="d-block text-muted">
                                                    Input: <?php echo htmlspecialchars($req['input_type']); ?> | 
                                                    Wajib: <?php echo $req['is_required'] ? 'Ya' : 'Tidak'; ?>
                                                </small>
                                            </div>
                                            <?php if ($req['options']): ?>
                                                <span class="badge bg-info">Pilihan: <?php echo htmlspecialchars($req['options']); ?></span>
                                            <?php endif; ?>
                                        </li>
                                        <?php endif; endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Persyaratan Khusus -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#khususCollapse">
                                    <i class="fas fa-star me-2"></i>Persyaratan Khusus
                                </button>
                            </h2>
                            <div id="khususCollapse" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    <ul class="list-group">
                                        <?php foreach ($vacancy['requirements'] as $req): 
                                            if ($req['requirement_type'] === 'khusus'): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <span><?php echo htmlspecialchars($req['requirement_text']); ?></span>
                                                <small class="d-block text-muted">
                                                    Input: <?php echo htmlspecialchars($req['input_type']); ?> | 
                                                    Wajib: <?php echo $req['is_required'] ? 'Ya' : 'Tidak'; ?>
                                                </small>
                                            </div>
                                            <?php if ($req['options']): ?>
                                                <span class="badge bg-info">Pilihan: <?php echo htmlspecialchars($req['options']); ?></span>
                                            <?php endif; ?>
                                        </li>
                                        <?php endif; endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Dokumen yang Diperlukan -->
                <div class="dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Dokumen Wajib</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($vacancy['documents'] as $doc): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?php echo htmlspecialchars($doc['document_name']); ?></span>
                            <span class="badge bg-<?php echo $doc['is_required'] ? 'danger' : 'warning'; ?>">
                                <?php echo $doc['is_required'] ? 'Wajib' : 'Opsional'; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Informasi Sistem -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Kode Lowongan</span>
                            <code><?php echo htmlspecialchars($vacancy['vacancy_code']); ?></code>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Dibuat Oleh</span>
                            <span><?php echo htmlspecialchars($vacancy['created_by_name'] ?? 'System'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Tanggal Dibuat</span>
                            <span><?php echo date('d/m/Y H:i', strtotime($vacancy['created_at'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Status</span>
                            <span class="badge bg-<?php echo $vacancy['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $vacancy['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </span>
                        </li>
                        <?php if (!empty($vacancy['formations'])): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Total Formasi</span>
                            <span class="badge bg-primary">
                                <?php 
                                $total = array_sum(array_column($vacancy['formations'], 'jumlah'));
                                echo $total . ' orang';
                                ?>
                            </span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.accordion-button {
    font-weight: 600;
}

.list-group-item {
    border-left: none;
    border-right: none;
}

.badge {
    font-size: 0.8em;
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
}

.formasi-row {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.formasi-row:last-child {
    border-bottom: none;
}
</style>

<?php
// Sertakan file JavaScript
echo '<script src="' . base_url('modules/admin/js-vacancy-management.js') . '"></script>';

// Inisialisasi data formasi untuk JavaScript
if ($action === 'edit' || $action === 'add') {
    echo '<script>';
    echo 'window.existingFormations = ' . (isset($vacancy['formations']) ? json_encode($vacancy['formations']) : '[]') . ';';
    echo '</script>';
}

include __DIR__ . '/../dashboard/footer-dashboard.php';
?>