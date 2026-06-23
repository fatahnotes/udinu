<?php
// modules/admin/vacancy-management.php - Manajemen Ujian
$pageTitle = "Manajemen Ujian";
$activePage = "vacancy-management";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

// Hanya SUPERADMIN
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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        $errors = [];
        if (empty($data['title'])) $errors[] = 'Judul ujian harus diisi';
        if (empty($data['vacancy_type_id'])) $errors[] = 'Jenis ujian harus dipilih';
        if ($data['open_date'] >= $data['close_date']) $errors[] = 'Tanggal tutup harus setelah tanggal buka';
        if ($data['tahun_angkatan'] < date('Y')) $errors[] = 'Tahun pelaksanaan tidak valid';
        
        if (empty($errors)) {
            if (isset($_POST['vacancy_id']) && $_POST['vacancy_id'] > 0) {
                $result = update_vacancy($db, $_POST['vacancy_id'], $data, $_SESSION['user_id']);
                if ($result) { $success = 'Ujian berhasil diperbarui'; $action = 'list'; }
                else { $error = 'Gagal memperbarui ujian.'; }
            } else {
                $vacancy_id = create_vacancy($db, $data, $_SESSION['user_id']);
                if ($vacancy_id) { $success = 'Ujian berhasil dibuat'; $action = 'list'; }
                else { $error = 'Gagal membuat ujian. Periksa log error.'; }
            }
        } else {
            $error = implode('<br>', $errors);
        }
    } elseif (isset($_POST['delete_vacancy'])) {
        $result = delete_vacancy($db, $_POST['vacancy_id'], $_SESSION['user_id']);
        $success = $result ? 'Ujian berhasil dihapus' : '';
        $error = $result ? '' : 'Gagal menghapus ujian';
        $action = 'list';
    }
}

// Ambil data
if ($action === 'edit' && $id > 0) {
    $vacancy = get_vacancy_details($db, $id);
    if (!$vacancy) { $error = 'Ujian tidak ditemukan'; $action = 'list'; }
} elseif ($action === 'view' && $id > 0) {
    $vacancy = get_vacancy_details($db, $id);
    if (!$vacancy) { $error = 'Ujian tidak ditemukan'; $action = 'list'; }
}

$vacancy_types = get_vacancy_types($db);

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5f8a 100%); color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-file-alt me-2"></i>Manajemen Ujian</h2>
                    <p class="mb-0 opacity-75">Kelola Ujian Dinas Tingkat I, Ujian Dinas Tingkat II, dan UPKP</p>
                </div>
                <a href="?action=add" class="btn btn-light fw-semibold px-4" style="color:#1a3a5c;">
                    <i class="fas fa-plus me-2"></i>Tambah Ujian
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'list' || $action === ''): ?>
<!-- Daftar Ujian -->
<div class="dashboard-card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom-0 pb-0">
        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Ujian</h4>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 exam-table">
            <thead>
                <tr>
                    <th class="col-kode">Kode Ujian</th>
                    <th class="col-judul">Judul Ujian</th>
                    <th class="col-jenis">Jenis</th>
                    <th class="col-tahun">Tahun</th>
                    <th class="col-periode">Periode Pendaftaran</th>
                    <th class="col-peserta">Peserta</th>
                    <th class="col-status">Status</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $vacancies = get_all_vacancies($db);
                if (empty($vacancies)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity:0.25"></i>
                            <span>Belum ada ujian yang dibuat</span>
                        </div>
                    </td>
                </tr>
                <?php else:
                foreach ($vacancies as $vac):
                    $is_active = $vac['is_active'];
                    $today = date('Y-m-d');
                    $is_open = ($today >= $vac['open_date'] && $today <= $vac['close_date'] && $is_active);
                    $is_closed = ($today > $vac['close_date']);
                    
                    // Warna per jenis ujian — pastikan kontras tinggi
                    $typeStyle = [
                        'UD1' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'icon' => 'level-up-alt'],
                        'UD2' => ['bg' => '#ffedd5', 'text' => '#9a3412', 'icon' => 'arrow-up'],
                        'UPKP' => ['bg' => '#dcfce7', 'text' => '#166534', 'icon' => 'exchange-alt'],
                    ];
                    $ts = $typeStyle[$vac['type_code']] ?? ['bg' => '#f1f5f9', 'text' => '#475569', 'icon' => 'file-alt'];
                ?>
                <tr>
                    <!-- Kode -->
                    <td class="col-kode">
                        <span class="exam-code"><?php echo htmlspecialchars($vac['vacancy_code']); ?></span>
                    </td>
                    
                    <!-- Judul -->
                    <td class="col-judul">
                        <div class="exam-title"><?php echo htmlspecialchars($vac['title']); ?></div>
                        <?php if (!empty($vac['description'])): ?>
                        <div class="exam-desc"><?php echo mb_substr(htmlspecialchars($vac['description']), 0, 70); ?>...</div>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Jenis -->
                    <td class="col-jenis">
                        <span class="exam-type-badge" style="background:<?php echo $ts['bg']; ?>;color:<?php echo $ts['text']; ?>">
                            <i class="fas fa-<?php echo $ts['icon']; ?>"></i>
                            <?php echo htmlspecialchars($vac['type_name']); ?>
                        </span>
                    </td>
                    
                    <!-- Tahun -->
                    <td class="col-tahun">
                        <span class="exam-year"><?php echo htmlspecialchars($vac['tahun_angkatan']); ?></span>
                    </td>
                    
                    <!-- Periode -->
                    <td class="col-periode">
                        <div class="period-range">
                            <span class="period-date"><?php echo date('d/m/Y', strtotime($vac['open_date'])); ?></span>
                            <i class="fas fa-long-arrow-alt-right period-arrow"></i>
                            <span class="period-date"><?php echo date('d/m/Y', strtotime($vac['close_date'])); ?></span>
                        </div>
                    </td>
                    
                    <!-- Peserta -->
                    <td class="col-peserta text-center">
                        <span class="participant-count"><?php echo (int)$vac['total_applicants']; ?></span>
                    </td>
                    
                    <!-- Status -->
                    <td class="col-status">
                        <?php if (!$is_active): ?>
                            <span class="status-chip status-inactive">Nonaktif</span>
                        <?php elseif ($is_open): ?>
                            <span class="status-chip status-open">Sedang Buka</span>
                        <?php elseif ($is_closed): ?>
                            <span class="status-chip status-closed">Ditutup</span>
                        <?php else: ?>
                            <span class="status-chip status-upcoming">Akan Dibuka</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Aksi -->
                    <td class="col-aksi">
                        <div class="action-btns">
                            <a href="?action=view&id=<?php echo $vac['id']; ?>" class="action-btn btn-view" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="?action=edit&id=<?php echo $vac['id']; ?>" class="action-btn btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="action-btn btn-delete"
                                    onclick="confirmDelete(<?php echo $vac['id']; ?>,'<?php echo htmlspecialchars(addslashes($vac['title'])); ?>')" title="Hapus">
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-1">Apakah Anda yakin ingin menghapus ujian:</p>
                <p class="fw-bold mb-0" id="deleteTitle"></p>
                <small class="text-danger"><i class="fas fa-info-circle me-1"></i>Tindakan ini tidak dapat dibatalkan.</small>
            </div>
            <div class="modal-footer border-0">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="vacancy_id" id="deleteId">
                    <input type="hidden" name="delete_vacancy" value="1">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<!-- Form Ujian -->
<div class="dashboard-card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom pb-3">
        <h4 class="mb-0">
            <i class="fas fa-<?php echo $action === 'add' ? 'plus-circle' : 'edit'; ?> me-2 text-primary"></i>
            <?php echo $action === 'add' ? 'Tambah Ujian Baru' : 'Edit Ujian'; ?>
        </h4>
    </div>
    
    <form method="POST" id="examForm">
        <input type="hidden" name="save_vacancy" value="1">
        <?php if (isset($vacancy['id'])): ?>
            <input type="hidden" name="vacancy_id" value="<?php echo $vacancy['id']; ?>">
        <?php endif; ?>
        
        <div class="row p-4">
            <!-- Jenis Ujian -->
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-tag me-1 text-primary"></i>Jenis Ujian <span class="text-danger">*</span>
                </label>
                <select class="form-select form-select-lg" name="vacancy_type_id" required
                    <?php echo isset($vacancy['id']) ? 'disabled' : ''; ?>>
                    <option value="">-- Pilih Jenis Ujian --</option>
                    <?php foreach ($vacancy_types as $type): ?>
                        <option value="<?php echo $type['id']; ?>"
                            <?php echo (isset($vacancy['vacancy_type_id']) && $vacancy['vacancy_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($vacancy['id'])): ?>
                    <input type="hidden" name="vacancy_type_id" value="<?php echo $vacancy['vacancy_type_id']; ?>">
                    <div class="form-text"><i class="fas fa-info-circle me-1"></i>Jenis ujian tidak dapat diubah setelah dibuat</div>
                <?php endif; ?>
            </div>
            
            <!-- Tahun Pelaksanaan -->
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar-alt me-1 text-primary"></i>Tahun Pelaksanaan <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-lg" name="tahun_angkatan" 
                       min="<?php echo date('Y'); ?>" max="<?php echo date('Y') + 5; ?>"
                       value="<?php echo isset($vacancy['tahun_angkatan']) ? $vacancy['tahun_angkatan'] : date('Y'); ?>" required>
            </div>
            
            <!-- Judul Ujian -->
            <div class="col-12 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-heading me-1 text-primary"></i>Judul Ujian <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-lg" name="title" 
                       placeholder="Contoh: Ujian Dinas Tingkat I Tahun Anggaran 2025"
                       value="<?php echo isset($vacancy['title']) ? htmlspecialchars($vacancy['title']) : ''; ?>" required>
            </div>
            
            <!-- Deskripsi -->
            <div class="col-12 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-align-left me-1 text-primary"></i>Deskripsi Ujian
                </label>
                <textarea class="form-control" name="description" rows="6" 
                          placeholder="Deskripsikan ujian ini: persyaratan peserta, materi ujian (TWK, TKP, TSI), metode CAT, ketentuan Makalah (untuk UD2), dan informasi penting lainnya..."><?php echo isset($vacancy['description']) ? htmlspecialchars($vacancy['description']) : ''; ?></textarea>
            </div>
            
            <!-- Tanggal Buka & Tutup -->
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar-check me-1 text-success"></i>Tanggal Pendaftaran Dibuka <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-lg" name="open_date" 
                       value="<?php echo isset($vacancy['open_date']) ? $vacancy['open_date'] : date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar-times me-1 text-danger"></i>Tanggal Pendaftaran Ditutup <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control form-control-lg" name="close_date" 
                       value="<?php echo isset($vacancy['close_date']) ? $vacancy['close_date'] : date('Y-m-d', strtotime('+30 days')); ?>" required>
            </div>
            
            <!-- Maksimal Peserta & Status -->
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-users me-1 text-primary"></i>Maksimal Peserta
                </label>
                <input type="number" class="form-control form-control-lg" name="max_applicants" 
                       min="1" placeholder="Kosongkan = tidak dibatasi"
                       value="<?php echo isset($vacancy['max_applicants']) ? $vacancy['max_applicants'] : ''; ?>">
                <div class="form-text">Biarkan kosong jika tidak ada batasan jumlah peserta</div>
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-toggle-on me-1 text-primary"></i>Status Ujian
                </label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                           id="isActiveSwitch" style="width:3em;height:1.5em;cursor:pointer"
                           <?php echo (!isset($vacancy['is_active']) || $vacancy['is_active']) ? 'checked' : ''; ?>>
                    <label class="form-check-label ms-2 fw-semibold" for="isActiveSwitch" id="isActiveLabel">
                        <?php echo (!isset($vacancy['is_active']) || $vacancy['is_active']) ? 'Aktif - Peserta dapat mendaftar' : 'Nonaktif - Tersembunyi dari peserta'; ?>
                    </label>
                </div>
            </div>
            
            <!-- Tombol -->
            <div class="col-12 pt-3 border-top">
                <button type="submit" class="btn btn-primary btn-lg px-4 me-3 shadow-sm">
                    <i class="fas fa-save me-2"></i>Simpan Ujian
                </button>
                <a href="?" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </form>
</div>

<?php elseif ($action === 'view'): ?>
<!-- Detail Ujian -->
<?php
$typeStyle = [
    'UD1' => ['bg' => '#e8f0fe', 'color' => '#1a56db', 'border' => '#1a56db', 'label' => 'Ujian Dinas Tingkat I'],
    'UD2' => ['bg' => '#fef3e2', 'color' => '#b45309', 'border' => '#b45309', 'label' => 'Ujian Dinas Tingkat II'],
    'UPKP' => ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'border' => '#2e7d32', 'label' => 'UPKP'],
];
$ts = $typeStyle[$vacancy['type_code']] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280', 'border' => '#6b7280', 'label' => $vacancy['type_name']];
$is_active = $vacancy['is_active'];
$today = date('Y-m-d');
$is_open = ($today >= $vacancy['open_date'] && $today <= $vacancy['close_date'] && $is_active);
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Header Detail -->
        <div class="dashboard-card border-0 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="badge px-3 py-2 mb-2" style="background:<?php echo $ts['bg']; ?>;color:<?php echo $ts['color']; ?>;font-weight:600;font-size:0.85rem">
                        <?php echo htmlspecialchars($vacancy['type_name']); ?>
                    </span>
                    <h3 class="mt-2 mb-1"><?php echo htmlspecialchars($vacancy['title']); ?></h3>
                    <p class="text-muted mb-0">Tahun Pelaksanaan <?php echo htmlspecialchars($vacancy['tahun_angkatan']); ?></p>
                </div>
                <div>
                    <a href="?action=edit&id=<?php echo $vacancy['id']; ?>" class="btn btn-primary shadow-sm">
                        <i class="fas fa-edit me-1"></i>Edit Ujian
                    </a>
                </div>
            </div>
            
            <?php if (!empty($vacancy['description'])): ?>
            <div class="p-3 rounded-3 mb-0" style="background:#f8fafc;border-left:4px solid <?php echo $ts['border']; ?>">
                <h6 class="fw-semibold mb-2">Deskripsi Ujian</h6>
                <p class="mb-0" style="line-height:1.8;color:#475569"><?php echo nl2br(htmlspecialchars($vacancy['description'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Periode Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body py-4">
                        <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                        <div class="text-muted small text-uppercase">Dibuka</div>
                        <div class="fw-bold fs-5"><?php echo date('d M Y', strtotime($vacancy['open_date'])); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body py-4">
                        <i class="fas fa-calendar-times fa-2x text-danger mb-2"></i>
                        <div class="text-muted small text-uppercase">Ditutup</div>
                        <div class="fw-bold fs-5"><?php echo date('d M Y', strtotime($vacancy['close_date'])); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body py-4">
                        <?php if ($is_open): ?>
                            <i class="fas fa-lock-open fa-2x text-success mb-2"></i>
                            <div class="text-muted small text-uppercase">Status</div>
                            <div class="fw-bold fs-5 text-success">Sedang Buka</div>
                        <?php elseif (!$is_active): ?>
                            <i class="fas fa-ban fa-2x text-secondary mb-2"></i>
                            <div class="text-muted small text-uppercase">Status</div>
                            <div class="fw-bold fs-5 text-secondary">Nonaktif</div>
                        <?php elseif ($today > $vacancy['close_date']): ?>
                            <i class="fas fa-lock fa-2x text-danger mb-2"></i>
                            <div class="text-muted small text-uppercase">Status</div>
                            <div class="fw-bold fs-5 text-danger">Ditutup</div>
                        <?php else: ?>
                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                            <div class="text-muted small text-uppercase">Status</div>
                            <div class="fw-bold fs-5 text-warning">Akan Dibuka</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informasi Spesifik Jenis Ujian -->
        <?php
        // Data spesifik per jenis ujian
        $examInfo = [
            'UD1' => [
                'title' => 'Ujian Dinas Tingkat I',
                'pangkat_asal' => 'Pengatur Tingkat I (II/d)',
                'pangkat_tujuan' => 'Penata Muda (III/a)',
                'min_tahun' => '1 tahun dalam pangkat II/d',
                'materi' => [
                    ['icon' => 'flag', 'label' => 'Tes Wawasan Kebangsaan (TWK)'],
                    ['icon' => 'book', 'label' => 'Tes Pengetahuan Umum (TKP)'],
                    ['icon' => 'building', 'label' => 'Tes Substansi Instansi (TSI)'],
                ],
                'metode' => 'Computer Assisted Test (CAT) — 100 soal',
                'catatan' => 'PNS yang tidak dikecualikan sesuai ketentuan perundang-undangan. Diusulkan oleh Pejabat Pimpinan Tinggi Pratama.',
            ],
            'UD2' => [
                'title' => 'Ujian Dinas Tingkat II',
                'pangkat_asal' => 'Penata Tingkat I (III/d)',
                'pangkat_tujuan' => 'Pembina (IV/a)',
                'min_tahun' => 'Menduduki Jabatan Struktural Administrator',
                'materi' => [
                    ['icon' => 'laptop', 'label' => 'Computer Assisted Test (CAT)'],
                    ['icon' => 'file-alt', 'label' => 'Penilaian Makalah Karya Tulis Ilmiah'],
                ],
                'metode' => 'CAT + Penilaian Makalah oleh Pejabat Pimpinan Tinggi Pratama',
                'catatan' => 'Belum mengikuti Pelatihan Kepemimpinan Administrator (PKA). Makalah sesuai TUPOKSI unit kerja.',
            ],
            'UPKP' => [
                'title' => 'Ujian Penyesuaian Kenaikan Pangkat (UPKP)',
                'pangkat_asal' => 'Sesuai pangkat/golongan saat ini',
                'pangkat_tujuan' => 'Disesuaikan dengan ijazah baru',
                'min_tahun' => 'Memiliki ijazah lebih tinggi dari jenjang saat ini',
                'materi' => [
                    ['icon' => 'graduation-cap', 'label' => 'Verifikasi Ijazah & Transkrip'],
                    ['icon' => 'file-contract', 'label' => 'SK Tugas Belajar / SKMPLT (jika ada)'],
                ],
                'metode' => 'Penyesuaian administratif berdasarkan ijazah lebih tinggi',
                'catatan' => 'Ijazah dari sekolah/perguruan tinggi terakreditasi. Diusulkan oleh Pejabat Pimpinan Tinggi Pratama.',
            ],
        ];
        $ei = $examInfo[$vacancy['type_code']] ?? null;
        ?>
        
        <?php if ($ei): ?>
        <!-- Kartu Informasi Spesifik Ujian -->
        <div class="dashboard-card border-0 shadow-sm mb-4" style="border-left:5px solid <?php echo $ts['border']; ?>">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="exam-info-icon" style="background:<?php echo $ts['bg']; ?>;color:<?php echo $ts['color']; ?>;width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <h5 class="mb-1" style="color:<?php echo $ts['color']; ?>"><?php echo $ei['title']; ?></h5>
                    <p class="text-muted mb-0 small">Informasi spesifik jenis ujian</p>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded-3" style="background:#f8fafc">
                        <div class="mb-3">
                            <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;letter-spacing:0.5px">Pangkat Asal</small>
                            <span class="fw-semibold text-dark"><?php echo $ei['pangkat_asal']; ?></span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;letter-spacing:0.5px">Pangkat Tujuan</small>
                            <span class="fw-semibold text-success"><?php echo $ei['pangkat_tujuan']; ?></span>
                        </div>
                        <div>
                            <small class="text-muted d-block text-uppercase" style="font-size:0.7rem;letter-spacing:0.5px">Syarat Khusus</small>
                            <span class="text-dark"><?php echo $ei['min_tahun']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded-3" style="background:#f8fafc">
                        <small class="text-muted d-block text-uppercase mb-2" style="font-size:0.7rem;letter-spacing:0.5px">Materi Ujian</small>
                        <?php foreach ($ei['materi'] as $m): ?>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fas fa-<?php echo $m['icon']; ?> text-muted" style="width:18px;font-size:0.8rem"></i>
                            <span class="text-dark" style="font-size:0.87rem"><?php echo $m['label']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 p-3 rounded-3" style="background:<?php echo $ts['bg']; ?>;color:<?php echo $ts['color']; ?>;font-size:0.85rem">
                <i class="fas fa-lightbulb me-2"></i><strong>Metode:</strong> <?php echo $ei['metode']; ?>
            </div>
            
            <?php if (!empty($ei['catatan'])): ?>
            <div class="mt-2">
                <small class="text-muted"><i class="fas fa-sticky-note me-1"></i><?php echo $ei['catatan']; ?></small>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Persyaratan Peserta -->
        <?php if (!empty($vacancy['requirements'])): ?>
        <div class="dashboard-card border-0 shadow-sm mb-4">
            <h5 class="mb-3"><i class="fas fa-clipboard-list me-2 text-primary"></i>Persyaratan Peserta</h5>
            <?php
            $umum = array_filter($vacancy['requirements'], fn($r) => $r['requirement_type'] === 'umum');
            $khusus = array_filter($vacancy['requirements'], fn($r) => $r['requirement_type'] === 'khusus');
            ?>
            <div class="accordion" id="reqAccordion">
                <div class="accordion-item border mb-2" style="border-radius:10px;overflow:hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button shadow-none fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#umumPanel" style="background:#f0f7ff">
                            <i class="fas fa-list-check me-2 text-primary"></i>Persyaratan Umum
                            <span class="badge bg-primary ms-2 rounded-pill"><?php echo count($umum); ?></span>
                        </button>
                    </h2>
                    <div id="umumPanel" class="accordion-collapse collapse show">
                        <div class="accordion-body pt-2">
                            <ul class="list-group list-group-flush">
                                <?php $no = 1; foreach ($umum as $req): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div>
                                        <span class="text-secondary me-2 small fw-bold"><?php echo $no++; ?>.</span>
                                        <span class="text-dark"><?php echo htmlspecialchars($req['requirement_text']); ?></span>
                                    </div>
                                    <span class="badge bg-<?php echo $req['is_required'] ? 'danger' : 'secondary'; ?> rounded-pill ms-2 flex-shrink-0">
                                        <?php echo $req['is_required'] ? 'Wajib' : 'Opsional'; ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php if (!empty($khusus)): ?>
                <div class="accordion-item border" style="border-radius:10px;overflow:hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed shadow-none fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#khususPanel" style="background:#fff8f0">
                            <i class="fas fa-star me-2 text-warning"></i>Persyaratan Khusus
                            <span class="badge bg-warning text-dark ms-2 rounded-pill"><?php echo count($khusus); ?></span>
                        </button>
                    </h2>
                    <div id="khususPanel" class="accordion-collapse collapse">
                        <div class="accordion-body pt-2">
                            <ul class="list-group list-group-flush">
                                <?php $no = 1; foreach ($khusus as $req): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div>
                                        <span class="text-secondary me-2 small fw-bold"><?php echo $no++; ?>.</span>
                                        <span class="text-dark"><?php echo htmlspecialchars($req['requirement_text']); ?></span>
                                    </div>
                                    <span class="badge bg-<?php echo $req['is_required'] ? 'danger' : 'secondary'; ?> rounded-pill ms-2 flex-shrink-0">
                                        <?php echo $req['is_required'] ? 'Wajib' : 'Opsional'; ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <!-- Informasi -->
        <div class="dashboard-card border-0 shadow-sm mb-4">
            <h5 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Informasi Ujian</h5>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                    <span class="text-muted">Kode</span>
                    <code class="fw-bold"><?php echo htmlspecialchars($vacancy['vacancy_code']); ?></code>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                    <span class="text-muted">Dibuat Oleh</span>
                    <span class="text-dark"><?php echo htmlspecialchars($vacancy['created_by_name'] ?? 'System'); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                    <span class="text-muted">Dibuat Tanggal</span>
                    <span class="text-dark"><?php echo date('d/m/Y H:i', strtotime($vacancy['created_at'])); ?></span>
                </li>
                <?php if ($vacancy['max_applicants']): ?>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                    <span class="text-muted">Kuota</span>
                    <span class="fw-bold text-dark"><?php echo number_format($vacancy['max_applicants']); ?> peserta</span>
                </li>
                <?php else: ?>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                    <span class="text-muted">Kuota</span>
                    <span class="text-success fw-bold">Tidak Terbatas</span>
                </li>
                <?php endif; ?>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                    <span class="text-muted">Status</span>
                    <?php if ($vacancy['is_active']): ?>
                    <span class="status-chip status-open">Aktif</span>
                    <?php else: ?>
                    <span class="status-chip status-inactive">Nonaktif</span>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
        
        <!-- Dokumen -->
        <?php if (!empty($vacancy['documents'])): ?>
        <div class="dashboard-card border-0 shadow-sm">
            <h5 class="mb-3"><i class="fas fa-file-alt me-2 text-primary"></i>Dokumen Wajib</h5>
            <ul class="list-group list-group-flush">
                <?php foreach ($vacancy['documents'] as $doc): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                    <span class="text-dark">
                        <i class="fas fa-<?php echo $doc['is_required'] ? 'file-alt text-danger' : 'file text-muted'; ?> me-2"></i>
                        <?php echo htmlspecialchars($doc['document_name']); ?>
                    </span>
                    <span class="badge bg-<?php echo $doc['is_required'] ? 'danger' : 'secondary'; ?> rounded-pill ms-2 flex-shrink-0">
                        <?php echo $doc['is_required'] ? 'Wajib' : 'Opsional'; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>/* All shared styles in modules/assets/css/style.css */</style>

<script>
function confirmDelete(id, title) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
document.getElementById('isActiveSwitch')?.addEventListener('change', function() {
    var label = document.getElementById('isActiveLabel');
    if (label) label.textContent = this.checked ? 'Aktif - Peserta dapat mendaftar' : 'Nonaktif - Tersembunyi dari peserta';
});
document.addEventListener('DOMContentLoaded', function() {
    var openDate = document.querySelector('input[name="open_date"]');
    var closeDate = document.querySelector('input[name="close_date"]');
    if (openDate && closeDate) {
        openDate.min = new Date().toISOString().split('T')[0];
        openDate.addEventListener('change', function() {
            closeDate.min = this.value;
            if (closeDate.value < this.value) closeDate.value = this.value;
        });
    }
});
</script>

<?php include __DIR__ . '/../dashboard/footer-dashboard.php'; ?>