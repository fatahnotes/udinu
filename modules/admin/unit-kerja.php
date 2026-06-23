<?php
// modules/admin/unit-kerja.php — Manajemen Unit Kerja & Verifikator
$pageTitle = "Manajemen Unit Kerja";
$activePage = "unit-kerja";
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
$error = '';
$success = '';
$tab = $_GET['tab'] ?? 'list'; // list | verifikator | upload

// =============================================
// HANDLE POST ACTIONS
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // === ADD / EDIT UNIT KERJA ===
    if ($action === 'save_unit') {
        $kode = strtoupper(trim($_POST['kode_satker']));
        $nama = trim($_POST['nama_satker']);
        $parent = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $level = trim($_POST['level']);
        $alamat = trim($_POST['alamat']);
        $telepon = trim($_POST['telepon']);
        $email = trim($_POST['email']);
        $active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($kode) || empty($nama)) {
            $error = 'Kode dan nama satker harus diisi';
        } else {
            try {
                if (!empty($_POST['id'])) {
                    $stmt = $db->prepare("UPDATE unit_kerja SET kode_satker=?, nama_satker=?, parent_id=?, level=?, alamat=?, telepon=?, email=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                    $stmt->execute([$kode, $nama, $parent, $level, $alamat, $telepon, $email, $active, intval($_POST['id'])]);
                    $success = 'Unit kerja berhasil diperbarui';
                } else {
                    $stmt = $db->prepare("INSERT INTO unit_kerja (kode_satker, nama_satker, parent_id, level, alamat, telepon, email, is_active) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([$kode, $nama, $parent, $level, $alamat, $telepon, $email, $active]);
                    $success = 'Unit kerja berhasil ditambahkan';
                }
            } catch (Exception $e) {
                $error = 'Gagal: ' . $e->getMessage();
            }
        }
    }
    
    // === DELETE UNIT KERJA ===
    if ($action === 'delete_unit') {
        try {
            $db->prepare("DELETE FROM unit_kerja_verifikator WHERE unit_kerja_id=?")->execute([intval($_POST['id'])]);
            $db->prepare("DELETE FROM unit_kerja WHERE id=?")->execute([intval($_POST['id'])]);
            $success = 'Unit kerja berhasil dihapus';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
    
    // === ASSIGN / REMOVE VERIFIKATOR ===
    if ($action === 'assign_verifikator') {
        $ukId = intval($_POST['unit_kerja_id']);
        $userId = intval($_POST['user_id']);
        try {
            $stmt = $db->prepare("INSERT INTO unit_kerja_verifikator (unit_kerja_id, user_id, assigned_by) VALUES (?,?,?) ON CONFLICT (unit_kerja_id, user_id) DO NOTHING");
            $stmt->execute([$ukId, $userId, $_SESSION['user_id']]);
            $success = 'Verifikator berhasil ditugaskan';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
    
    if ($action === 'remove_verifikator') {
        try {
            $db->prepare("DELETE FROM unit_kerja_verifikator WHERE id=?")->execute([intval($_POST['id'])]);
            $success = 'Verifikator berhasil dihapus dari unit kerja';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
    
    // === EXCEL UPLOAD ===
    if ($action === 'upload_excel') {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload gagal. Silakan coba lagi.';
        } else {
            $file = $_FILES['excel_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
                $error = 'Format file tidak valid. Gunakan CSV, XLSX, atau XLS.';
            } else {
                $tmpPath = $file['tmp_name'];
                $handle = null;
                
                // Try CSV first (simplest)
                if ($ext === 'csv') {
                    $handle = fopen($tmpPath, 'r');
                    if ($handle) {
                        $header = fgetcsv($handle); // Skip header row
                        $inserted = 0;
                        $skipped = 0;
                        $db->beginTransaction();
                        try {
                            while (($row = fgetcsv($handle)) !== false) {
                                if (count($row) < 3) { $skipped++; continue; }
                                $kode = strtoupper(trim($row[0] ?? ''));
                                $nama = trim($row[1] ?? '');
                                $level = trim($row[2] ?? 'satker');
                                $alamat = trim($row[3] ?? '');
                                $telepon = trim($row[4] ?? '');
                                $email = trim($row[5] ?? '');
                                $parentKode = trim($row[6] ?? '');
                                
                                if (empty($kode) || empty($nama)) { $skipped++; continue; }
                                
                                $parentId = null;
                                if (!empty($parentKode)) {
                                    $ps = $db->prepare("SELECT id FROM unit_kerja WHERE kode_satker=? LIMIT 1");
                                    $ps->execute([$parentKode]);
                                    $pr = $ps->fetch();
                                    if ($pr) $parentId = (int)$pr['id'];
                                }
                                
                                $stmt = $db->prepare("INSERT INTO unit_kerja (kode_satker, nama_satker, level, alamat, telepon, email, parent_id) VALUES (?,?,?,?,?,?,?) ON CONFLICT (kode_satker) DO UPDATE SET nama_satker=EXCLUDED.nama_satker, level=EXCLUDED.level, alamat=EXCLUDED.alamat, telepon=EXCLUDED.telepon, email=EXCLUDED.email");
                                $stmt->execute([$kode, $nama, $level, $alamat, $telepon, $email, $parentId]);
                                $inserted++;
                            }
                            $db->commit();
                            $success = "Upload berhasil: $inserted data diimpor, $skipped dilewati.";
                        } catch (Exception $e) {
                            $db->rollBack();
                            $error = 'Gagal import: ' . $e->getMessage();
                        }
                        fclose($handle);
                    }
                } else {
                    // XLSX/XLS — gunakan library PhpSpreadsheet jika tersedia
                    $error = 'Untuk file XLSX/XLS, gunakan format CSV. Silakan download template dan simpan sebagai CSV.';
                }
            }
        }
    }
}

// =============================================
// FETCH DATA
// =============================================
$units = $db->query("
    SELECT u.*, COALESCE(p.nama_satker, '—') as parent_name,
           (SELECT COUNT(*) FROM unit_kerja_verifikator WHERE unit_kerja_id = u.id) as total_verifikator
    FROM unit_kerja u
    LEFT JOIN unit_kerja p ON u.parent_id = p.id
    ORDER BY COALESCE(u.parent_id, u.id), u.level, u.kode_satker
")->fetchAll();

// Untuk dropdown parent
$parentUnits = $db->query("SELECT id, kode_satker, nama_satker FROM unit_kerja ORDER BY kode_satker")->fetchAll();

// Verifikator mapping untuk tab verifikator
$verifikator_list = [];
if ($tab === 'verifikator') {
    $verifikator_list = $db->query("
        SELECT ukv.*, uk.kode_satker, uk.nama_satker as unit_name,
               u.full_name, u.email
        FROM unit_kerja_verifikator ukv
        JOIN unit_kerja uk ON ukv.unit_kerja_id = uk.id
        JOIN users u ON ukv.user_id = u.id
        ORDER BY uk.kode_satker, u.full_name
    ")->fetchAll();
}

// Get ADMIN_VERIFIKATOR users for assignment dropdown
$verifikator_users = $db->query("
    SELECT u.id, u.full_name, u.email, uk.kode_satker, uk.nama_satker
    FROM users u
    JOIN user_roles ur ON u.id = ur.user_id
    JOIN roles r ON ur.role_id = r.id
    LEFT JOIN unit_kerja_verifikator ukv ON u.id = ukv.user_id
    LEFT JOIN unit_kerja uk ON ukv.unit_kerja_id = uk.id
    WHERE r.role_code = 'ADMIN_VERIFIKATOR' AND u.is_active = TRUE
    ORDER BY u.full_name
")->fetchAll();

// Untuk edit unit
$editUnit = null;
if (isset($_GET['edit_id'])) {
    $stmt = $db->prepare("SELECT * FROM unit_kerja WHERE id=?");
    $stmt->execute([intval($_GET['edit_id'])]);
    $editUnit = $stmt->fetch();
}

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5f8a 100%); color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-sitemap me-2"></i>Manajemen Unit Kerja</h2>
                    <p class="mb-0 opacity-75">Kelola satuan kerja dan penugasan verifikator</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item"><a class="nav-link <?php echo $tab==='list'?'active':''; ?>" href="?tab=list"><i class="fas fa-list me-1"></i>Daftar Unit Kerja</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab==='verifikator'?'active':''; ?>" href="?tab=verifikator"><i class="fas fa-user-check me-1"></i>Mapping Verifikator</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $tab==='upload'?'active':''; ?>" href="?tab=upload"><i class="fas fa-upload me-1"></i>Upload Excel</a></li>
</ul>

<?php if ($tab === 'list'): ?>
<!-- ========== TAB: DAFTAR UNIT KERJA ========== -->
<div class="dashboard-card border-0 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-building me-2 text-primary"></i>Daftar Unit Kerja</h5>
        <button class="btn btn-primary btn-sm" onclick="openUnitForm()"><i class="fas fa-plus me-1"></i>Tambah Unit Kerja</button>
    </div>
    <div class="table-responsive">
        <table class="table align-middle exam-table">
            <thead>
                <tr>
                    <th style="width:100px">Kode</th>
                    <th>Nama Satker</th>
                    <th style="width:100px">Level</th>
                    <th style="width:150px">Parent</th>
                    <th style="width:80px">Verifikator</th>
                    <th style="width:80px">Status</th>
                    <th style="width:120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($units)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Belum ada unit kerja</td></tr>
                <?php else: foreach ($units as $u): ?>
                <tr>
                    <td><code class="fw-bold"><?php echo htmlspecialchars($u['kode_satker']); ?></code></td>
                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($u['nama_satker']); ?></td>
                    <td><span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($u['level'])); ?></span></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($u['parent_name']); ?></small></td>
                    <td class="text-center"><span class="badge bg-secondary rounded-pill"><?php echo $u['total_verifikator']; ?></span></td>
                    <td><?php echo $u['is_active'] ? '<span class="status-chip status-open">Aktif</span>' : '<span class="status-chip status-inactive">Nonaktif</span>'; ?></td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn btn-edit" onclick="editUnit(<?php echo htmlspecialchars(json_encode($u)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="action-btn btn-delete" onclick="deleteUnit(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars(addslashes($u['nama_satker'])); ?>')" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Unit Modal -->
<div class="modal fade" id="unitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="unitModalTitle"><i class="fas fa-plus-circle me-2"></i>Tambah Unit Kerja</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_unit"><input type="hidden" name="id" id="unitId">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Kode Satker <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_satker" id="unitKode" placeholder="Contoh: DITJEN-DIKTI" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Nama Satker <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_satker" id="unitNama" placeholder="Nama lengkap satuan kerja" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Level</label>
                            <select class="form-select" name="level" id="unitLevel">
                                <option value="eselon1">Eselon I</option>
                                <option value="eselon2">Eselon II</option>
                                <option value="satker">Eselon III</option>
                                <option value="upt">Eselon IV</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Parent</label>
                            <select class="form-select" name="parent_id" id="unitParent">
                                <option value="">— Tidak Ada —</option>
                                <?php foreach ($parentUnits as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['kode_satker'].' - '.$p['nama_satker']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Telepon</label>
                            <input type="text" class="form-control" name="telepon" id="unitTelp" placeholder="021-xxxx">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea class="form-control" name="alamat" id="unitAlamat" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email" id="unitEmail" placeholder="satker@kemdiktisaintek.go.id">
                            <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="unitActive" checked><label class="form-check-label" for="unitActive">Aktif</label></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteUnitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body py-4">
                    <input type="hidden" name="action" value="delete_unit"><input type="hidden" name="id" id="deleteUnitId">
                    <p class="mb-1">Hapus unit kerja: <strong id="deleteUnitName"></strong>?</p>
                    <small class="text-danger"><i class="fas fa-info-circle me-1"></i>Semua mapping verifikator pada unit ini juga akan terhapus.</small>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Hapus</button></div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($tab === 'verifikator'): ?>
<!-- ========== TAB: MAPPING VERIFIKATOR ========== -->
<div class="row">
    <!-- Assign Form -->
    <div class="col-lg-5">
        <div class="dashboard-card border-0 shadow-sm mb-4">
            <h5 class="mb-3"><i class="fas fa-user-plus me-2 text-primary"></i>Tugaskan Verifikator ke Unit Kerja</h5>
            <form method="POST">
                <input type="hidden" name="action" value="assign_verifikator">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Unit Kerja <span class="text-danger">*</span></label>
                    <select class="form-select" name="unit_kerja_id" required>
                        <option value="">— Pilih Unit Kerja —</option>
                        <?php foreach ($units as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['kode_satker'].' — '.$u['nama_satker']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Verifikator <span class="text-danger">*</span></label>
                    <select class="form-select" name="user_id" required>
                        <option value="">— Pilih Verifikator —</option>
                        <?php 
                        $shown = [];
                        foreach ($verifikator_users as $vu): 
                            if (in_array($vu['id'], $shown)) continue; $shown[] = $vu['id'];
                        ?>
                        <option value="<?php echo $vu['id']; ?>"><?php echo htmlspecialchars($vu['full_name'].' ('.$vu['email'].')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-link me-1"></i>Tugaskan Verifikator</button>
            </form>
            <hr>
            <div class="p-3 rounded-3" style="background:#f0f7ff">
                <small><i class="fas fa-info-circle me-1 text-primary"></i>Verifikator yang ditugaskan ke suatu unit kerja hanya dapat memverifikasi peserta dari unit kerja tersebut.</small>
            </div>
        </div>
    </div>
    
    <!-- Mapping List -->
    <div class="col-lg-7">
        <div class="dashboard-card border-0 shadow-sm">
            <h5 class="mb-3"><i class="fas fa-list me-2 text-primary"></i>Daftar Penugasan Verifikator <small class="text-muted">(<?php echo count($verifikator_list); ?>)</small></h5>
            <div class="table-responsive">
                <table class="table align-middle exam-table">
                    <thead><tr><th>Verifikator</th><th>Unit Kerja</th><th style="width:180px">Ditugaskan</th><th style="width:70px">Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($verifikator_list)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada verifikator yang ditugaskan</td></tr>
                        <?php else: foreach ($verifikator_list as $vk): ?>
                        <tr>
                            <td><div class="fw-semibold text-dark"><?php echo htmlspecialchars($vk['full_name']); ?></div><small class="text-muted"><?php echo htmlspecialchars($vk['email']); ?></small></td>
                            <td><code><?php echo htmlspecialchars($vk['kode_satker']); ?></code> <span class="text-dark"><?php echo htmlspecialchars($vk['unit_name']); ?></span></td>
                            <td><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($vk['assigned_at'])); ?></small></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Hapus penugasan verifikator ini?')">
                                    <input type="hidden" name="action" value="remove_verifikator">
                                    <input type="hidden" name="id" value="<?php echo $vk['id']; ?>">
                                    <button type="submit" class="action-btn btn-delete" title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'upload'): ?>
<!-- ========== TAB: UPLOAD EXCEL ========== -->
<div class="row">
    <div class="col-lg-7">
        <div class="dashboard-card border-0 shadow-sm mb-4">
            <h5 class="mb-3"><i class="fas fa-upload me-2 text-primary"></i>Upload Data Unit Kerja (CSV)</h5>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_excel">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih File CSV</label>
                    <input type="file" class="form-control" name="excel_file" accept=".csv" required>
                    <div class="form-text">Format yang diterima: CSV (Comma Separated Values). Maksimal 2MB.</div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Upload & Import</button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="dashboard-card border-0 shadow-sm">
            <h5 class="mb-3"><i class="fas fa-download me-2 text-success"></i>Download Template</h5>
            <p class="text-muted small">Gunakan template CSV berikut untuk mengisi data unit kerja secara massal, lalu upload kembali.</p>
            <a href="?download_template=1" class="btn btn-success w-100"><i class="fas fa-download me-1"></i>Download Template CSV</a>
            <hr>
            <div class="p-3 rounded-3" style="background:#f0f7ff">
                <small class="fw-semibold d-block mb-1">Format Kolom CSV:</small>
                <table class="table table-sm table-borderless mb-0 small">
                    <tr><td style="width:30px">A</td><td><code>kode_satker</code></td><td class="text-muted">Kode unik (contoh: DITJEN-DIKTI)</td></tr>
                    <tr><td>B</td><td><code>nama_satker</code></td><td class="text-muted">Nama lengkap satker</td></tr>
                    <tr><td>C</td><td><code>level</code></td><td class="text-muted">eselon1 / eselon2 / satker / upt</td></tr>
                    <tr><td>D</td><td><code>alamat</code></td><td class="text-muted">Alamat (opsional)</td></tr>
                    <tr><td>E</td><td><code>telepon</code></td><td class="text-muted">Telepon (opsional)</td></tr>
                    <tr><td>F</td><td><code>email</code></td><td class="text-muted">Email satker (opsional)</td></tr>
                    <tr><td>G</td><td><code>parent_kode</code></td><td class="text-muted">Kode satker induk (opsional)</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>/* Shared CSS in modules/assets/css/style.css */</style>

<script>
function openUnitForm() {
    document.getElementById('unitModalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Tambah Unit Kerja';
    document.getElementById('unitId').value = '';
    document.getElementById('unitKode').value = '';
    document.getElementById('unitNama').value = '';
    document.getElementById('unitLevel').value = 'satker';
    document.getElementById('unitParent').value = '';
    document.getElementById('unitTelp').value = '';
    document.getElementById('unitAlamat').value = '';
    document.getElementById('unitEmail').value = '';
    document.getElementById('unitActive').checked = true;
    new bootstrap.Modal(document.getElementById('unitModal')).show();
}
function editUnit(data) {
    document.getElementById('unitModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Unit Kerja';
    document.getElementById('unitId').value = data.id;
    document.getElementById('unitKode').value = data.kode_satker;
    document.getElementById('unitNama').value = data.nama_satker;
    document.getElementById('unitLevel').value = data.level;
    document.getElementById('unitParent').value = data.parent_id || '';
    document.getElementById('unitTelp').value = data.telepon || '';
    document.getElementById('unitAlamat').value = data.alamat || '';
    document.getElementById('unitEmail').value = data.email || '';
    document.getElementById('unitActive').checked = data.is_active == 1 || data.is_active == true;
    new bootstrap.Modal(document.getElementById('unitModal')).show();
}
function deleteUnit(id, name) {
    document.getElementById('deleteUnitId').value = id;
    document.getElementById('deleteUnitName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteUnitModal')).show();
}
</script>

<?php
// =============================================
// DOWNLOAD TEMPLATE CSV
// =============================================
if (isset($_GET['download_template'])) {
    // Clear any output before sending CSV
    ob_clean();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template-unit-kerja.csv"');
    header('Pragma: no-cache');
    
    $output = fopen('php://output', 'w');
    // BOM untuk Excel UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['kode_satker', 'nama_satker', 'level', 'alamat', 'telepon', 'email', 'parent_kode']);
    fputcsv($output, ['DITJEN-DIKTI', 'Direktorat Jenderal Pendidikan Tinggi', 'eselon1', 'Jl. Sudirman, Jakarta', '021-123456', 'dikti@kemdiktisaintek.go.id', '']);
    fputcsv($output, ['SETDITJEN-DIKTI', 'Sekretariat Ditjen Dikti', 'eselon2', 'Jl. Sudirman, Jakarta', '021-123457', 'setditjendikti@kemdiktisaintek.go.id', 'DITJEN-DIKTI']);
    fputcsv($output, ['UPT-PUSLATDIK', 'UPT Pusat Pelatihan Pendidikan', 'upt', 'Jl. Merdeka, Bandung', '022-234567', 'puslatdik@kemdiktisaintek.go.id', '']);
    fclose($output);
    exit;
}

include __DIR__ . '/../dashboard/footer-dashboard.php'; ?>