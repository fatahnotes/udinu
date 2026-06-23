<?php
// modules/admin/jabatan-management.php — Manajemen Jabatan
$pageTitle = "Manajemen Jabatan";
$activePage = "jabatan-management";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';
require_login();
if ($_SESSION['user_role'] !== 'SUPERADMIN') { header('Location: '.base_url('modules/dashboard/dashboard.php')); exit; }
$db = get_db_connection();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $kode = strtoupper(trim($_POST['kode']));
        $nama = trim($_POST['nama_jabatan']);
        $kategori = trim($_POST['kategori']);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (empty($kode)||empty($nama)) $error = 'Kode dan nama harus diisi';
        else try {
            if (!empty($_POST['id'])) {
                $db->prepare("UPDATE jabatan SET kode=?,nama_jabatan=?,kategori=?,is_active=? WHERE id=?")->execute([$kode,$nama,$kategori,$active,intval($_POST['id'])]);
                $success = 'Jabatan diperbarui';
            } else {
                $db->prepare("INSERT INTO jabatan (kode,nama_jabatan,kategori,is_active) VALUES (?,?,?,?)")->execute([$kode,$nama,$kategori,$active]);
                $success = 'Jabatan ditambahkan';
            }
        } catch(Exception $e) { $error = $e->getMessage(); }
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM jabatan WHERE id=?")->execute([intval($_POST['id'])]);
        $success = 'Jabatan dihapus';
    }
}

$jabatans = $db->query("SELECT * FROM jabatan ORDER BY kategori, kode")->fetchAll();
include __DIR__.'/../dashboard/header-dashboard.php';
?>

<div class="row mb-4"><div class="col-12"><div class="dashboard-card" style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5f8a 100%); color: white;"><div class="d-flex justify-content-between align-items-center"><div><h2 class="mb-1"><i class="fas fa-briefcase me-2"></i>Manajemen Jabatan</h2><p class="mb-0 opacity-75">Kelola daftar jabatan ASN</p></div><button class="btn btn-light fw-semibold" onclick="openForm()"><i class="fas fa-plus me-1"></i>Tambah Jabatan</button></div></div></div></div>
<?php if($error): ?><div class="alert alert-danger border-0 shadow-sm"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success border-0 shadow-sm"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="dashboard-card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle exam-table">
            <thead><tr><th style="width:160px">Kode</th><th>Nama Jabatan</th><th style="width:120px">Kategori</th><th style="width:80px">Status</th><th style="width:110px">Aksi</th></tr></thead>
            <tbody>
                <?php if(empty($jabatans)): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada data jabatan</td></tr>
                <?php else: foreach($jabatans as $j): ?>
                <tr><td><code class="fw-bold"><?php echo htmlspecialchars($j['kode']); ?></code></td>
                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($j['nama_jabatan']); ?></td>
                    <td><span class="badge bg-<?php echo $j['kategori']==='fungsional'?'info':($j['kategori']==='struktural'?'warning':'secondary'); ?>"><?php echo ucfirst($j['kategori']); ?></span></td>
                    <td><?php echo $j['is_active']?'<span class="status-chip status-open">Aktif</span>':'<span class="status-chip status-inactive">Nonaktif</span>'; ?></td>
                    <td><div class="action-btns">
                        <button class="action-btn btn-edit" onclick="editJabatan(<?php echo htmlspecialchars(json_encode($j)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="action-btn btn-delete" onclick="deleteJabatan(<?php echo $j['id']; ?>,'<?php echo htmlspecialchars(addslashes($j['nama_jabatan'])); ?>')" title="Hapus"><i class="fas fa-trash"></i></button>
                    </div></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="jabatanModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content border-0 shadow">
    <form method="POST"><div class="modal-header bg-primary text-white"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Tambah Jabatan</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" name="action" value="save"><input type="hidden" name="id" id="jabId">
        <div class="row"><div class="col-md-5 mb-3"><label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label><input type="text" class="form-control" name="kode" id="jabKode" placeholder="JFU-GURU-MUDA" required></div>
        <div class="col-md-7 mb-3"><label class="form-label fw-semibold">Nama Jabatan <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama_jabatan" id="jabNama" placeholder="Guru Muda" required></div></div>
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label fw-semibold">Kategori</label><select class="form-select" name="kategori" id="jabKat"><option value="fungsional">Fungsional</option><option value="struktural">Struktural</option><option value="pelaksana">Pelaksana</option></select></div>
        <div class="col-md-6 d-flex align-items-end mb-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="jabActive" checked><label class="form-check-label" for="jabActive">Aktif</label></div></div></div>
    </div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
    </form>
</div></div></div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
    <form method="POST"><div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body py-4"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delId"><p>Hapus jabatan: <strong id="delName"></strong>?</p></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Hapus</button></div></form>
</div></div></div>

<style>/* Using shared CSS from style.css */</style>
<script>
function openForm(){document.getElementById('modalTitle').innerHTML='<i class="fas fa-plus-circle me-2"></i>Tambah Jabatan';document.getElementById('jabId').value='';document.getElementById('jabKode').value='';document.getElementById('jabNama').value='';document.getElementById('jabKat').value='fungsional';document.getElementById('jabActive').checked=true;new bootstrap.Modal(document.getElementById('jabatanModal')).show();}
function editJabatan(d){document.getElementById('modalTitle').innerHTML='<i class="fas fa-edit me-2"></i>Edit Jabatan';document.getElementById('jabId').value=d.id;document.getElementById('jabKode').value=d.kode;document.getElementById('jabNama').value=d.nama_jabatan;document.getElementById('jabKat').value=d.kategori;document.getElementById('jabActive').checked=d.is_active==1||d.is_active==true;new bootstrap.Modal(document.getElementById('jabatanModal')).show();}
function deleteJabatan(id,name){document.getElementById('delId').value=id;document.getElementById('delName').textContent=name;new bootstrap.Modal(document.getElementById('deleteModal')).show();}
</script>
<?php include __DIR__.'/../dashboard/footer-dashboard.php'; ?>