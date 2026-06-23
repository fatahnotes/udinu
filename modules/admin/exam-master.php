<?php
// modules/admin/exam-master.php - Master Data Ujian
$pageTitle = "Master Data Ujian";
$activePage = "exam-master";
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
$tab = $_GET['tab'] ?? 'types'; // types | documents | requirements

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // === UPDATE EXAM TYPE ===
    if ($action === 'update_type') {
        $id = intval($_POST['type_id']);
        $code = strtoupper(trim($_POST['type_code']));
        $name = trim($_POST['type_name']);
        $desc = trim($_POST['description']);
        $active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($code) || empty($name)) {
            $error = 'Kode dan nama jenis ujian harus diisi';
        } else {
            try {
                $stmt = $db->prepare("UPDATE vacancy_types SET type_code=?, type_name=?, description=?, is_active=? WHERE id=?");
                $stmt->execute([$code, $name, $desc, $active, $id]);
                $success = 'Jenis ujian berhasil diperbarui';
            } catch (Exception $e) {
                $error = 'Gagal: ' . $e->getMessage();
            }
        }
    }
    
    // === ADD EXAM TYPE ===
    if ($action === 'add_type') {
        $code = strtoupper(trim($_POST['type_code']));
        $name = trim($_POST['type_name']);
        $desc = trim($_POST['description']);
        
        if (empty($code) || empty($name)) {
            $error = 'Kode dan nama jenis ujian harus diisi';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO vacancy_types (type_code, type_name, description, is_active) VALUES (?,?,?,TRUE)");
                $stmt->execute([$code, $name, $desc]);
                $success = 'Jenis ujian berhasil ditambahkan';
            } catch (Exception $e) {
                $error = 'Gagal: ' . $e->getMessage();
            }
        }
    }
    
    // === UPDATE DEFAULT DOCUMENT ===
    if ($action === 'update_document') {
        $id = intval($_POST['doc_id']);
        $name = trim($_POST['document_name']);
        $code = trim($_POST['document_code']);
        $required = isset($_POST['is_required']) ? 1 : 0;
        $order = intval($_POST['display_order']);
        
        try {
            $stmt = $db->prepare("UPDATE vacancy_documents SET document_name=?, document_code=?, is_required=?, display_order=? WHERE id=?");
            $stmt->execute([$name, $code, $required, $order, $id]);
            $success = 'Dokumen berhasil diperbarui';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
    
    // === ADD DEFAULT DOCUMENT ===
    if ($action === 'add_document') {
        $templateId = intval($_POST['template_vacancy_id']);
        $name = trim($_POST['document_name']);
        $code = trim($_POST['document_code']);
        $required = isset($_POST['is_required']) ? 1 : 0;
        $order = intval($_POST['display_order']);
        
        if (empty($name) || empty($code)) {
            $error = 'Nama dan kode dokumen harus diisi';
        } elseif ($templateId <= 0) {
            $error = 'Tidak ada ujian aktif untuk jenis ini. Buat ujian terlebih dahulu.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO vacancy_documents (vacancy_id, document_name, document_code, is_required, display_order) VALUES (?,?,?,?,?)");
                $stmt->execute([$templateId, $name, $code, $required, $order]);
                $success = 'Dokumen berhasil ditambahkan';
            } catch (Exception $e) {
                $error = 'Gagal: ' . $e->getMessage();
            }
        }
    }
    
    // === DELETE DEFAULT DOCUMENT ===
    if ($action === 'delete_document') {
        $id = intval($_POST['doc_id']);
        try {
            $stmt = $db->prepare("DELETE FROM vacancy_documents WHERE id=?");
            $stmt->execute([$id]);
            $success = 'Dokumen berhasil dihapus';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
    
    // === UPDATE DEFAULT REQUIREMENT ===
    if ($action === 'update_requirement') {
        $id = intval($_POST['req_id']);
        $type = trim($_POST['requirement_type']);
        $text = trim($_POST['requirement_text']);
        $input = trim($_POST['input_type']);
        $required = isset($_POST['is_required']) ? 1 : 0;
        $order = intval($_POST['display_order']);
        
        try {
            $stmt = $db->prepare("UPDATE vacancy_requirements SET requirement_type=?, requirement_text=?, input_type=?, is_required=?, display_order=? WHERE id=?");
            $stmt->execute([$type, $text, $input, $required, $order, $id]);
            $success = 'Persyaratan berhasil diperbarui';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
    
    // === ADD DEFAULT REQUIREMENT ===
    if ($action === 'add_requirement') {
        $templateId = intval($_POST['template_vacancy_id']);
        $rtype = trim($_POST['requirement_type']);
        $rtext = trim($_POST['requirement_text']);
        $rinput = trim($_POST['input_type']);
        $rrequired = isset($_POST['is_required']) ? 1 : 0;
        $rorder = intval($_POST['display_order']);
        
        if (empty($rtext)) {
            $error = 'Teks persyaratan harus diisi';
        } elseif ($templateId <= 0) {
            $error = 'Tidak ada ujian aktif untuk jenis ini. Buat ujian terlebih dahulu.';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO vacancy_requirements (vacancy_id, requirement_type, requirement_text, input_type, is_required, display_order) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$templateId, $rtype, $rtext, $rinput, $rrequired, $rorder]);
                $success = 'Persyaratan berhasil ditambahkan';
            } catch (Exception $e) {
                $error = 'Gagal: ' . $e->getMessage();
            }
        }
    }
    
    // === DELETE DEFAULT REQUIREMENT ===
    if ($action === 'delete_requirement') {
        $id = intval($_POST['req_id']);
        try {
            $stmt = $db->prepare("DELETE FROM vacancy_requirements WHERE id=?");
            $stmt->execute([$id]);
            $success = 'Persyaratan berhasil dihapus';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
}

// Fetch data
$exam_types = $db->query("SELECT * FROM vacancy_types ORDER BY id")->fetchAll();

// Get selected type for documents/requirements
$selected_type_id = intval($_GET['type_id'] ?? 0);
if (!$selected_type_id && !empty($exam_types)) {
    $selected_type_id = $exam_types[0]['id'];
}

$type_documents = [];
$type_requirements = [];
$selected_type = null;
$template_vacancy_id = 0; // ID vacancy yg jadi template untuk add

if ($selected_type_id) {
    $stmt = $db->prepare("SELECT * FROM vacancy_types WHERE id = ?");
    $stmt->execute([$selected_type_id]);
    $selected_type = $stmt->fetch();
    
    // Cari vacancy aktif untuk dijadikan template
    $stmt = $db->prepare("SELECT id FROM vacancies WHERE vacancy_type_id = ? AND is_active = TRUE ORDER BY id LIMIT 1");
    $stmt->execute([$selected_type_id]);
    $tv = $stmt->fetch();
    if ($tv) $template_vacancy_id = (int)$tv['id'];
    
    if ($tab === 'documents' || $tab === 'requirements') {
        // Get documents from template vacancy
        if ($template_vacancy_id) {
            $stmt = $db->prepare("SELECT * FROM vacancy_documents WHERE vacancy_id = ? ORDER BY display_order");
            $stmt->execute([$template_vacancy_id]);
            $type_documents = $stmt->fetchAll();
        }
        
        // Get requirements from template vacancy
        if ($template_vacancy_id) {
            $stmt = $db->prepare("SELECT * FROM vacancy_requirements WHERE vacancy_id = ? ORDER BY requirement_type, display_order");
            $stmt->execute([$template_vacancy_id]);
            $type_requirements = $stmt->fetchAll();
        }
    }
}

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<!-- Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5f8a 100%); color: white;">
            <div>
                <h2 class="mb-1"><i class="fas fa-database me-2"></i>Master Data Ujian</h2>
                <p class="mb-0 opacity-75">Kelola jenis ujian, dokumen default, dan persyaratan default</p>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Tab Navigation -->
<ul class="nav nav-pills mb-4 gap-2" id="masterTabs">
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'types' ? 'active' : ''; ?>" href="?tab=types">
            <i class="fas fa-tags me-1"></i>Jenis Ujian
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'documents' ? 'active' : ''; ?>" href="?tab=documents&type_id=<?php echo $selected_type_id; ?>">
            <i class="fas fa-file-alt me-1"></i>Dokumen Default
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'requirements' ? 'active' : ''; ?>" href="?tab=requirements&type_id=<?php echo $selected_type_id; ?>">
            <i class="fas fa-clipboard-list me-1"></i>Persyaratan Default
        </a>
    </li>
</ul>

<?php if ($tab === 'types'): ?>
<!-- ========== TAB: JENIS UJIAN ========== -->
<div class="dashboard-card border-0 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="fas fa-tags me-2 text-primary"></i>Daftar Jenis Ujian</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTypeModal">
            <i class="fas fa-plus me-1"></i>Tambah Jenis
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table align-middle exam-table">
            <thead>
                <tr>
                    <th style="width:80px">ID</th>
                    <th style="width:100px">Kode</th>
                    <th>Nama</th>
                    <th>Deskripsi</th>
                    <th style="width:90px">Status</th>
                    <th style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exam_types as $type): 
                    $ts = [
                        'UD1' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'UD2' => ['bg' => '#ffedd5', 'text' => '#9a3412'],
                        'UPKP' => ['bg' => '#dcfce7', 'text' => '#166534'],
                    ];
                    $tc = $ts[$type['type_code']] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];
                ?>
                <tr>
                    <td><span class="text-muted">#<?php echo $type['id']; ?></span></td>
                    <td>
                        <span class="badge px-3 py-2" style="background:<?php echo $tc['bg']; ?>;color:<?php echo $tc['text']; ?>;font-weight:700;font-size:0.8rem">
                            <?php echo htmlspecialchars($type['type_code']); ?>
                        </span>
                    </td>
                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($type['type_name']); ?></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars(mb_substr($type['description'] ?? '', 0, 100)); ?></small></td>
                    <td>
                        <?php if ($type['is_active']): ?>
                            <span class="status-chip status-open">Aktif</span>
                        <?php else: ?>
                            <span class="status-chip status-inactive">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="action-btn btn-edit" onclick="editType(<?php echo $type['id']; ?>,'<?php echo htmlspecialchars($type['type_code']); ?>','<?php echo htmlspecialchars(addslashes($type['type_name'])); ?>','<?php echo htmlspecialchars(addslashes($type['description'] ?? '')); ?>',<?php echo $type['is_active'] ? 'true' : 'false'; ?>)" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Type Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Jenis Ujian</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_type">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="type_code" placeholder="Contoh: UD1" maxlength="20" required>
                        <div class="form-text">Kode unik, maksimal 20 karakter (contoh: UD1, UD2, UPKP)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="type_name" placeholder="Contoh: Ujian Dinas Tingkat I" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Deskripsi singkat jenis ujian..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Type Modal -->
<div class="modal fade" id="editTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Jenis Ujian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_type">
                    <input type="hidden" name="type_id" id="editTypeId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="type_code" id="editTypeCode" maxlength="20" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="type_name" id="editTypeName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea class="form-control" name="description" id="editTypeDesc" rows="3"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editTypeActive">
                        <label class="form-check-label" for="editTypeActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($tab === 'documents'): ?>
<!-- ========== TAB: DOKUMEN DEFAULT ========== -->
<div class="dashboard-card border-0 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="fas fa-file-alt me-2 text-primary"></i>
            Dokumen Default: 
            <span class="badge px-3 py-2 ms-2" style="background:<?php echo ($selected_type['type_code'] ?? '') === 'UD1' ? '#dbeafe' : (($selected_type['type_code'] ?? '') === 'UD2' ? '#ffedd5' : '#dcfce7'); ?>;color:<?php echo ($selected_type['type_code'] ?? '') === 'UD1' ? '#1e40af' : (($selected_type['type_code'] ?? '') === 'UD2' ? '#9a3412' : '#166534'); ?>">
                <?php echo htmlspecialchars($selected_type['type_name'] ?? 'Pilih Jenis'); ?>
            </span>
        </h5>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width:250px" onchange="window.location='?tab=documents&type_id='+this.value">
                <?php foreach ($exam_types as $type): ?>
                <option value="<?php echo $type['id']; ?>" <?php echo $selected_type_id == $type['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['type_code'] . ' - ' . $type['type_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <p class="text-muted small mb-3">
        <i class="fas fa-info-circle me-1"></i>Dokumen-dokumen ini akan otomatis ditambahkan saat membuat ujian baru dengan jenis ini. 
        Kelola di sini untuk menambah, mengedit, atau menghapus dokumen default.
    </p>
    
    <div class="d-flex justify-content-end mb-2">
        <?php if ($template_vacancy_id): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDocModal">
            <i class="fas fa-plus me-1"></i>Tambah Dokumen
        </button>
        <?php else: ?>
        <span class="text-muted small"><i class="fas fa-exclamation-triangle me-1"></i>Buat ujian aktif untuk jenis ini terlebih dahulu agar bisa menambah dokumen</span>
        <?php endif; ?>
    </div>
    
    <div class="table-responsive">
        <table class="table align-middle exam-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Nama Dokumen</th>
                    <th style="width:150px">Kode</th>
                    <th style="width:90px">Wajib</th>
                    <th style="width:80px">Urutan</th>
                    <th style="width:130px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($type_documents)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                        Belum ada dokumen default. Klik "Tambah Dokumen" untuk menambahkan.
                    </td>
                </tr>
                <?php else: foreach ($type_documents as $i => $doc): ?>
                <tr>
                    <td><span class="text-muted"><?php echo $i + 1; ?></span></td>
                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($doc['document_name']); ?></td>
                    <td><code><?php echo htmlspecialchars($doc['document_code']); ?></code></td>
                    <td>
                        <?php if ($doc['is_required']): ?>
                            <span class="status-chip status-open">Wajib</span>
                        <?php else: ?>
                            <span class="status-chip status-inactive">Opsional</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><span class="exam-year"><?php echo $doc['display_order']; ?></span></td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn btn-edit" onclick="editDocument(<?php echo $doc['id']; ?>,'<?php echo htmlspecialchars(addslashes($doc['document_name'])); ?>','<?php echo htmlspecialchars($doc['document_code']); ?>',<?php echo $doc['is_required'] ? 'true' : 'false'; ?>,<?php echo $doc['display_order']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="confirmDeleteDoc(<?php echo $doc['id']; ?>,'<?php echo htmlspecialchars(addslashes($doc['document_name'])); ?>')" title="Hapus">
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

<!-- Add Document Modal -->
<div class="modal fade" id="addDocModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Dokumen Default</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_document">
                    <input type="hidden" name="template_vacancy_id" value="<?php echo $template_vacancy_id; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Dokumen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="document_name" placeholder="Contoh: Surat Usulan Pimpinan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="document_code" placeholder="Contoh: surat_usulan" required>
                        <div class="form-text">Kode unik untuk identifikasi dokumen (huruf kecil, underscore)</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" class="form-control" name="display_order" value="1" min="1" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_required" value="1" checked id="addDocRequired">
                                <label class="form-check-label" for="addDocRequired">Dokumen Wajib</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Document Modal -->
<div class="modal fade" id="editDocModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Dokumen Default</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_document">
                    <input type="hidden" name="doc_id" id="editDocId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Dokumen</label>
                        <input type="text" class="form-control" name="document_name" id="editDocName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode</label>
                        <input type="text" class="form-control" name="document_code" id="editDocCode" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Urutan</label>
                        <input type="number" class="form-control" name="display_order" id="editDocOrder" min="1" required>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="editDocRequired">
                        <label class="form-check-label" for="editDocRequired">Dokumen Wajib</label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($tab === 'requirements'): ?>
<!-- ========== TAB: PERSYARATAN DEFAULT ========== -->
<div class="dashboard-card border-0 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="fas fa-clipboard-list me-2 text-primary"></i>
            Persyaratan Default: 
            <span class="badge px-3 py-2 ms-2" style="background:<?php echo ($selected_type['type_code'] ?? '') === 'UD1' ? '#dbeafe' : (($selected_type['type_code'] ?? '') === 'UD2' ? '#ffedd5' : '#dcfce7'); ?>;color:<?php echo ($selected_type['type_code'] ?? '') === 'UD1' ? '#1e40af' : (($selected_type['type_code'] ?? '') === 'UD2' ? '#9a3412' : '#166534'); ?>">
                <?php echo htmlspecialchars($selected_type['type_name'] ?? 'Pilih Jenis'); ?>
            </span>
        </h5>
        <select class="form-select form-select-sm" style="width:250px" onchange="window.location='?tab=requirements&type_id='+this.value">
            <?php foreach ($exam_types as $type): ?>
            <option value="<?php echo $type['id']; ?>" <?php echo $selected_type_id == $type['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($type['type_code'] . ' - ' . $type['type_name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <p class="text-muted small mb-3">
        <i class="fas fa-info-circle me-1"></i>Persyaratan ini akan otomatis ditambahkan saat membuat ujian baru. 
        Kelola di sini untuk menambah, mengedit, atau menghapus persyaratan default.
    </p>
    
    <div class="d-flex justify-content-end mb-2">
        <?php if ($template_vacancy_id): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addReqModal">
            <i class="fas fa-plus me-1"></i>Tambah Persyaratan
        </button>
        <?php else: ?>
        <span class="text-muted small"><i class="fas fa-exclamation-triangle me-1"></i>Buat ujian aktif untuk jenis ini terlebih dahulu agar bisa menambah persyaratan</span>
        <?php endif; ?>
    </div>
    
    <?php
    $umum = array_filter($type_requirements, fn($r) => $r['requirement_type'] === 'umum');
    $khusus = array_filter($type_requirements, fn($r) => $r['requirement_type'] === 'khusus');
    ?>
    
    <?php if (!empty($umum)): ?>
    <h6 class="mb-2 text-primary"><i class="fas fa-list-check me-1"></i>Persyaratan Umum <small class="text-muted">(<?php echo count($umum); ?>)</small></h6>
    <div class="table-responsive mb-4">
        <table class="table align-middle exam-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Teks Persyaratan</th>
                    <th style="width:100px">Input</th>
                    <th style="width:80px">Wajib</th>
                    <th style="width:70px">Urutan</th>
                    <th style="width:130px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($umum as $i => $req): ?>
                <tr>
                    <td><span class="text-muted"><?php echo $i + 1; ?></span></td>
                    <td class="text-dark"><?php echo htmlspecialchars($req['requirement_text']); ?></td>
                    <td><code><?php echo htmlspecialchars($req['input_type']); ?></code></td>
                    <td><?php echo $req['is_required'] ? '<span class="status-chip status-open">Wajib</span>' : '<span class="status-chip status-inactive">Opsional</span>'; ?></td>
                    <td class="text-center"><span class="exam-year"><?php echo $req['display_order']; ?></span></td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn btn-edit" onclick="editRequirement(<?php echo $req['id']; ?>,'<?php echo htmlspecialchars($req['requirement_type']); ?>','<?php echo htmlspecialchars(addslashes($req['requirement_text'])); ?>','<?php echo htmlspecialchars($req['input_type']); ?>',<?php echo $req['is_required'] ? 'true' : 'false'; ?>,<?php echo $req['display_order']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="confirmDeleteReq(<?php echo $req['id']; ?>,'<?php echo htmlspecialchars(addslashes(mb_substr($req['requirement_text'], 0, 50))); ?>')" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($khusus)): ?>
    <h6 class="mb-2 text-warning"><i class="fas fa-star me-1"></i>Persyaratan Khusus <small class="text-muted">(<?php echo count($khusus); ?>)</small></h6>
    <div class="table-responsive">
        <table class="table align-middle exam-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Teks Persyaratan</th>
                    <th style="width:100px">Input</th>
                    <th style="width:80px">Wajib</th>
                    <th style="width:70px">Urutan</th>
                    <th style="width:130px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($khusus as $i => $req): ?>
                <tr>
                    <td><span class="text-muted"><?php echo $i + 1; ?></span></td>
                    <td class="text-dark"><?php echo htmlspecialchars($req['requirement_text']); ?></td>
                    <td><code><?php echo htmlspecialchars($req['input_type']); ?></code></td>
                    <td><?php echo $req['is_required'] ? '<span class="status-chip status-open">Wajib</span>' : '<span class="status-chip status-inactive">Opsional</span>'; ?></td>
                    <td class="text-center"><span class="exam-year"><?php echo $req['display_order']; ?></span></td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn btn-edit" onclick="editRequirement(<?php echo $req['id']; ?>,'<?php echo htmlspecialchars($req['requirement_type']); ?>','<?php echo htmlspecialchars(addslashes($req['requirement_text'])); ?>','<?php echo htmlspecialchars($req['input_type']); ?>',<?php echo $req['is_required'] ? 'true' : 'false'; ?>,<?php echo $req['display_order']; ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn btn-delete" onclick="confirmDeleteReq(<?php echo $req['id']; ?>,'<?php echo htmlspecialchars(addslashes(mb_substr($req['requirement_text'], 0, 50))); ?>')" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (empty($type_requirements)): ?>
    <div class="text-center text-muted py-4">
        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
        Belum ada persyaratan default. Klik "Tambah Persyaratan" untuk menambahkan.
    </div>
    <?php endif; ?>
</div>

<!-- Add Requirement Modal -->
<div class="modal fade" id="addReqModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Persyaratan Default</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_requirement">
                    <input type="hidden" name="template_vacancy_id" value="<?php echo $template_vacancy_id; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Persyaratan</label>
                        <select class="form-select" name="requirement_type" required>
                            <option value="umum">Umum</option>
                            <option value="khusus">Khusus</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Teks Persyaratan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="requirement_text" rows="3" placeholder="Tuliskan teks persyaratan..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Input</label>
                        <select class="form-select" name="input_type" required>
                            <option value="file">Upload File</option>
                            <option value="text">Teks</option>
                            <option value="radio">Pilihan (Radio)</option>
                            <option value="validation">Validasi Sistem</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" class="form-control" name="display_order" value="1" min="1" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_required" value="1" checked id="addReqRequired">
                                <label class="form-check-label" for="addReqRequired">Wajib</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Requirement Modal -->
<div class="modal fade" id="editReqModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Persyaratan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_requirement">
                    <input type="hidden" name="req_id" id="editReqId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe</label>
                        <select class="form-select" name="requirement_type" id="editReqType">
                            <option value="umum">Umum</option>
                            <option value="khusus">Khusus</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Teks Persyaratan</label>
                        <textarea class="form-control" name="requirement_text" id="editReqText" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipe Input</label>
                        <select class="form-select" name="input_type" id="editReqInput">
                            <option value="file">Upload File</option>
                            <option value="text">Teks</option>
                            <option value="radio">Pilihan (Radio)</option>
                            <option value="validation">Validasi Sistem</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_required" value="1" id="editReqRequired">
                                <label class="form-check-label" for="editReqRequired">Wajib</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" class="form-control" name="display_order" id="editReqOrder" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Document Modal -->
<div class="modal fade" id="deleteDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Dokumen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" name="doc_id" id="deleteDocId">
                    <p class="mb-1">Apakah Anda yakin ingin menghapus dokumen:</p>
                    <p class="fw-bold mb-0" id="deleteDocName"></p>
                    <small class="text-danger"><i class="fas fa-info-circle me-1"></i>Tindakan ini tidak dapat dibatalkan.</small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Requirement Modal -->
<div class="modal fade" id="deleteReqModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Persyaratan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="hidden" name="action" value="delete_requirement">
                    <input type="hidden" name="req_id" id="deleteReqId">
                    <p class="mb-1">Apakah Anda yakin ingin menghapus persyaratan:</p>
                    <p class="fw-bold mb-0" id="deleteReqName"></p>
                    <small class="text-danger"><i class="fas fa-info-circle me-1"></i>Tindakan ini tidak dapat dibatalkan.</small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
/* Shared table & component styles (same as vacancy-management) */
.exam-table { border-collapse: separate; border-spacing: 0; }
.exam-table thead th { background: #f8fafc; color: #475569; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.6px; padding: 14px 16px; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.exam-table tbody td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.exam-table tbody tr:hover { background: #f8fafc; }
.exam-table tbody tr:last-child td { border-bottom: none; }
.status-chip { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; letter-spacing: 0.2px; white-space: nowrap; }
.status-open { background: #dcfce7; color: #166534; }
.status-inactive { background: #f1f5f9; color: #64748b; }
.exam-year { color: #334155; font-weight: 700; font-size: 0.95rem; }
.action-btn { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; cursor: pointer; transition: all 0.2s; font-size: 0.85rem; text-decoration: none; }
.action-btns { display: flex; gap: 6px; }
.btn-edit { color: #3b82f6; }
.btn-delete { color: #ef4444; }
.action-btn:hover { transform: translateY(-1px); }
.btn-edit:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
.btn-delete:hover { background: #fef2f2; border-color: #fca5a5; color: #dc2626; }
.nav-pills .nav-link { color: #475569; border-radius: 8px; padding: 10px 20px; font-weight: 500; transition: all 0.2s; }
.nav-pills .nav-link:hover { background: #f1f5f9; color: #1e293b; }
.nav-pills .nav-link.active { background: #1a3a5c; color: #fff; }
code { background: #f1f5f9; padding: 2px 8px; border-radius: 4px; color: #1e293b; font-size: 0.82rem; }
</style>

<script>
function editType(id, code, name, desc, active) {
    document.getElementById('editTypeId').value = id;
    document.getElementById('editTypeCode').value = code;
    document.getElementById('editTypeName').value = name;
    document.getElementById('editTypeDesc').value = desc || '';
    document.getElementById('editTypeActive').checked = active;
    new bootstrap.Modal(document.getElementById('editTypeModal')).show();
}
function editDocument(id, name, code, required, order) {
    document.getElementById('editDocId').value = id;
    document.getElementById('editDocName').value = name;
    document.getElementById('editDocCode').value = code;
    document.getElementById('editDocRequired').checked = required;
    document.getElementById('editDocOrder').value = order;
    new bootstrap.Modal(document.getElementById('editDocModal')).show();
}
function confirmDeleteDoc(id, name) {
    document.getElementById('deleteDocId').value = id;
    document.getElementById('deleteDocName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteDocModal')).show();
}
function editRequirement(id, type, text, input, required, order) {
    document.getElementById('editReqId').value = id;
    document.getElementById('editReqType').value = type;
    document.getElementById('editReqText').value = text;
    document.getElementById('editReqInput').value = input;
    document.getElementById('editReqRequired').checked = required;
    document.getElementById('editReqOrder').value = order;
    new bootstrap.Modal(document.getElementById('editReqModal')).show();
}
function confirmDeleteReq(id, text) {
    document.getElementById('deleteReqId').value = id;
    document.getElementById('deleteReqName').textContent = text + '...';
    new bootstrap.Modal(document.getElementById('deleteReqModal')).show();
}
</script>

<?php include __DIR__ . '/../dashboard/footer-dashboard.php'; ?>