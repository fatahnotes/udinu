<?php
// modules/admin/menu-management.php - Manajemen Menu Dinamis
$pageTitle = "Manajemen Menu";
$activePage = "menu-management";
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

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $role = trim($_POST['role_code']);
        $label = trim($_POST['label']);
        $icon = trim($_POST['icon']);
        $url = trim($_POST['url']);
        $activeKey = trim($_POST['active_key']);
        $order = intval($_POST['display_order']);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $visible = isset($_POST['is_visible']) ? 1 : 0;
        
        if (empty($label)) {
            $error = 'Label menu harus diisi';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order, is_active, is_visible) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$parentId, $role, $label, $icon, $url, $activeKey, $order, $active, $visible]);
                    $success = 'Menu berhasil ditambahkan';
                } else {
                    $id = intval($_POST['id']);
                    $stmt = $db->prepare("UPDATE menus SET parent_id=?, role_code=?, label=?, icon=?, url=?, active_key=?, display_order=?, is_active=?, is_visible=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                    $stmt->execute([$parentId, $role, $label, $icon, $url, $activeKey, $order, $active, $visible, $id]);
                    $success = 'Menu berhasil diperbarui';
                }
            } catch (Exception $e) {
                $error = 'Gagal: ' . $e->getMessage();
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        try {
            $db->prepare("DELETE FROM menus WHERE id=?")->execute([$id]);
            $success = 'Menu berhasil dihapus';
        } catch (Exception $e) {
            $error = 'Gagal: ' . $e->getMessage();
        }
    }
}

// Fetch data
$menus = $db->query("
    SELECT m.*, COALESCE(p.label, '—') as parent_label
    FROM menus m
    LEFT JOIN menus p ON m.parent_id = p.id
    ORDER BY m.role_code, COALESCE(m.parent_id, 0), m.display_order
")->fetchAll();

$parent_options = $db->query("SELECT id, role_code, label FROM menus WHERE parent_id IS NULL ORDER BY role_code, display_order")->fetchAll();

// Group by role for display
$menu_tree = [];
foreach ($menus as $m) {
    $role = $m['role_code'];
    if (!isset($menu_tree[$role])) $menu_tree[$role] = ['parents' => [], 'children' => []];
    if ($m['parent_id'] === null) {
        $menu_tree[$role]['parents'][$m['id']] = $m;
    } else {
        $menu_tree[$role]['children'][] = $m;
    }
}

$roles = ['SUPERADMIN', 'ADMIN_VERIFIKATOR', 'ASSESSOR', 'USER'];

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5f8a 100%); color: white;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1"><i class="fas fa-bars me-2"></i>Manajemen Menu Dinamis</h2>
                    <p class="mb-0 opacity-75">Kelola struktur menu sidebar untuk setiap role</p>
                </div>
                <button class="btn btn-light fw-semibold" onclick="addMenu()">
                    <i class="fas fa-plus me-2"></i>Tambah Menu
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php foreach ($roles as $role): ?>
<?php if (!isset($menu_tree[$role])) continue; ?>
<div class="dashboard-card border-0 shadow-sm mb-4">
    <h5 class="mb-3">
        <span class="badge px-3 py-2 me-2" style="background:<?php echo $role === 'SUPERADMIN' ? '#dbeafe' : ($role === 'ADMIN_VERIFIKATOR' ? '#fef3e2' : ($role === 'ASSESSOR' ? '#fce7f3' : '#dcfce7')); ?>;color:<?php echo $role === 'SUPERADMIN' ? '#1e40af' : ($role === 'ADMIN_VERIFIKATOR' ? '#b45309' : ($role === 'ASSESSOR' ? '#9d174d' : '#166534')); ?>;font-weight:700">
            <?php echo $role; ?>
        </span>
    </h5>
    
    <div class="table-responsive">
        <table class="table align-middle exam-table">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Label</th>
                    <th style="width:120px">Parent</th>
                    <th style="width:100px">Icon</th>
                    <th style="width:120px">URL</th>
                    <th style="width:100px">Active Key</th>
                    <th style="width:60px">Urut</th>
                    <th style="width:85px">Status</th>
                    <th style="width:110px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; ?>
                <?php foreach ($menu_tree[$role]['parents'] as $pid => $parent): ?>
                <tr style="background:#f8fafc">
                    <td><span class="text-muted"><?php echo $no++; ?></span></td>
                    <td class="fw-bold text-dark"><i class="fas fa-<?php echo $parent['icon']; ?> me-2 text-primary"></i><?php echo htmlspecialchars($parent['label']); ?></td>
                    <td><span class="text-muted small">— ROOT</span></td>
                    <td><code><?php echo htmlspecialchars($parent['icon']); ?></code></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($parent['url']); ?></small></td>
                    <td><code><?php echo htmlspecialchars($parent['active_key']); ?></code></td>
                    <td class="text-center"><?php echo $parent['display_order']; ?></td>
                    <td><?php echo $parent['is_active'] ? '<span class="status-chip status-open">Aktif</span>' : '<span class="status-chip status-inactive">Nonaktif</span>'; ?></td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn btn-edit" onclick="editMenu(<?php echo htmlspecialchars(json_encode($parent)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="action-btn btn-delete" onclick="deleteMenu(<?php echo $parent['id']; ?>,'<?php echo htmlspecialchars($parent['label']); ?>')" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php 
                // Children of this parent
                $children = array_filter($menu_tree[$role]['children'], fn($c) => $c['parent_id'] == $pid);
                foreach ($children as $child): 
                ?>
                <tr>
                    <td><span class="text-muted"><?php echo $no++; ?></span></td>
                    <td class="ps-4">
                        <i class="fas fa-<?php echo $child['icon']; ?> me-2 text-secondary"></i>
                        <?php echo htmlspecialchars($child['label']); ?>
                    </td>
                    <td><span class="text-muted small">↳ <?php echo htmlspecialchars($child['parent_label']); ?></span></td>
                    <td><code><?php echo htmlspecialchars($child['icon']); ?></code></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($child['url']); ?></small></td>
                    <td><code><?php echo htmlspecialchars($child['active_key']); ?></code></td>
                    <td class="text-center"><?php echo $child['display_order']; ?></td>
                    <td><?php echo $child['is_active'] ? '<span class="status-chip status-open">Aktif</span>' : '<span class="status-chip status-inactive">Nonaktif</span>'; ?></td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn btn-edit" onclick="editMenu(<?php echo htmlspecialchars(json_encode($child)); ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="action-btn btn-delete" onclick="deleteMenu(<?php echo $child['id']; ?>,'<?php echo htmlspecialchars($child['label']); ?>')" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if (empty($menu_tree[$role]['parents'])): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">Belum ada menu untuk role ini</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<!-- Add/Edit Menu Modal -->
<div class="modal fade" id="menuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i><span id="menuModalTitle">Tambah Menu</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="menuAction" value="add">
                    <input type="hidden" name="id" id="menuId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent</label>
                        <select class="form-select" name="parent_id" id="menuParent">
                            <option value="">— ROOT (Menu Utama) —</option>
                            <?php foreach ($parent_options as $po): ?>
                            <option value="<?php echo $po['id']; ?>"><?php echo htmlspecialchars($po['role_code'] . ' › ' . $po['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role_code" id="menuRole" required>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Display Order</label>
                            <input type="number" class="form-control" name="display_order" id="menuOrder" value="1" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="label" id="menuLabel" placeholder="Contoh: Manajemen Ujian" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Icon (Font Awesome)</label>
                            <input type="text" class="form-control" name="icon" id="menuIcon" placeholder="Contoh: file-alt" value="circle">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Active Key</label>
                            <input type="text" class="form-control" name="active_key" id="menuActiveKey" placeholder="Contoh: dashboard">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL</label>
                        <input type="text" class="form-control" name="url" id="menuUrl" placeholder="Untuk parent pakai #, untuk child: ../admin/page.php" value="#">
                        <div class="form-text">Gunakan # untuk menu parent (hanya grouping), path relatif dari modules/dashboard/ untuk child</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="menuActive" checked>
                                <label class="form-check-label" for="menuActive">Aktif</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_visible" value="1" id="menuVisible" checked>
                                <label class="form-check-label" for="menuVisible">Visible</label>
                            </div>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteMenuModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteMenuId">
                    <p class="mb-1">Apakah Anda yakin ingin menghapus menu:</p>
                    <p class="fw-bold mb-0" id="deleteMenuLabel"></p>
                    <small class="text-danger"><i class="fas fa-info-circle me-1"></i>Jika menu ini memiliki sub-menu, semua sub-menu akan ikut terhapus.</small>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.exam-table { border-collapse: separate; border-spacing: 0; }
.exam-table thead th { background: #f8fafc; color: #475569; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.6px; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
.exam-table tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 0.87rem; }
.exam-table tbody tr:hover { background: #f8fafc; }
.status-chip { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
.status-open { background: #dcfce7; color: #166534; }
.status-inactive { background: #f1f5f9; color: #64748b; }
.action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; cursor: pointer; transition: all 0.2s; font-size: 0.8rem; }
.action-btns { display: flex; gap: 5px; }
.btn-edit { color: #3b82f6; }
.btn-delete { color: #ef4444; }
.action-btn:hover { transform: translateY(-1px); }
.btn-edit:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
.btn-delete:hover { background: #fef2f2; border-color: #fca5a5; color: #dc2626; }
code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #1e293b; font-size: 0.78rem; }
</style>

<script>
function addMenu() {
    document.getElementById('menuAction').value = 'add';
    document.getElementById('menuId').value = '';
    document.getElementById('menuModalTitle').textContent = 'Tambah Menu';
    document.getElementById('menuParent').value = '';
    document.getElementById('menuRole').value = 'SUPERADMIN';
    document.getElementById('menuLabel').value = '';
    document.getElementById('menuIcon').value = 'circle';
    document.getElementById('menuUrl').value = '#';
    document.getElementById('menuActiveKey').value = '';
    document.getElementById('menuOrder').value = '1';
    document.getElementById('menuActive').checked = true;
    document.getElementById('menuVisible').checked = true;
    new bootstrap.Modal(document.getElementById('menuModal')).show();
}

function editMenu(data) {
    document.getElementById('menuAction').value = 'edit';
    document.getElementById('menuId').value = data.id;
    document.getElementById('menuModalTitle').textContent = 'Edit Menu';
    document.getElementById('menuParent').value = data.parent_id || '';
    document.getElementById('menuRole').value = data.role_code;
    document.getElementById('menuLabel').value = data.label;
    document.getElementById('menuIcon').value = data.icon;
    document.getElementById('menuUrl').value = data.url;
    document.getElementById('menuActiveKey').value = data.active_key || '';
    document.getElementById('menuOrder').value = data.display_order;
    document.getElementById('menuActive').checked = data.is_active == 1 || data.is_active == true;
    document.getElementById('menuVisible').checked = data.is_visible == 1 || data.is_visible == true;
    new bootstrap.Modal(document.getElementById('menuModal')).show();
}

function deleteMenu(id, label) {
    document.getElementById('deleteMenuId').value = id;
    document.getElementById('deleteMenuLabel').textContent = label;
    new bootstrap.Modal(document.getElementById('deleteMenuModal')).show();
}
</script>

<?php include __DIR__ . '/../dashboard/footer-dashboard.php'; ?>