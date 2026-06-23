<?php
// modules/profile/profile.php — Profil Pengguna
$pageTitle = "Profil Saya";
$activePage = "profile";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';
require_login();

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$error = $success = '';
$mode = in_array($_GET['mode'] ?? '', ['view','edit']) ? $_GET['mode'] : 'view';

// Fetch profile + related data
$stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ?"); $stmt->execute([$user_id]); $profile = $stmt->fetch();
$stmt = $db->prepare("SELECT mata_pelajaran FROM user_subjects WHERE user_id = ?"); $stmt->execute([$user_id]); $user_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get reference data
$unit_list = $db->query("SELECT id, kode_satker, nama_satker FROM unit_kerja WHERE is_active = TRUE ORDER BY kode_satker")->fetchAll();
$jabatan_list = $db->query("SELECT id, kode, nama_jabatan, kategori FROM jabatan WHERE is_active = TRUE ORDER BY kategori, nama_jabatan")->fetchAll();
$golongan_list = ['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e'];

// Allowed modes for view
$allowed_view = ['profile','dashboard'];

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { $error = 'Token tidak valid.'; }
    else {
        $gender = sanitize_input($_POST['gender'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
        $last_education = sanitize_input($_POST['last_education'] ?? '');
        $major = sanitize_input($_POST['major'] ?? '');
        $unit_kerja_id = !empty($_POST['unit_kerja_id']) ? intval($_POST['unit_kerja_id']) : null;
        $golongan = $_POST['golongan'] ?? '';
        $jabatan_id = !empty($_POST['jabatan_id']) ? intval($_POST['jabatan_id']) : null;
        $status_pekerjaan = $_POST['status_pekerjaan'] ?? '';
        $address = sanitize_input($_POST['address'] ?? '');
        $provinsi = sanitize_input($_POST['provinsi'] ?? '');
        $mata_pelajaran = $_POST['mata_pelajaran'] ?? [];
        
        $errors = [];
        if (!in_array($gender, ['Laki-laki','Perempuan'])) $errors[] = 'Jenis kelamin harus dipilih.';
        if (empty($nip) || !preg_match('/^\d{9,18}$/', $nip)) $errors[] = 'NIP harus diisi (9-18 digit angka).';
        if (!preg_match('/^08[1-9][0-9]{7,9}$/', $phone)) $errors[] = 'Nomor HP harus 10-12 digit dimulai 08.';
        if (empty($tanggal_lahir)) $errors[] = 'Tanggal lahir harus diisi.';
        if (empty($last_education)) $errors[] = 'Pendidikan terakhir harus dipilih.';
        if (empty($major) || strlen($major) < 2) $errors[] = 'Program studi harus diisi.';
        if (!$unit_kerja_id) $errors[] = 'Instansi / Unit Kerja harus dipilih.';
        if (empty($golongan)) $errors[] = 'Golongan harus dipilih.';
        if (!$jabatan_id) $errors[] = 'Jabatan harus dipilih.';
        if (empty($status_pekerjaan)) $errors[] = 'Status pekerjaan harus dipilih.';
        if (empty($address) || strlen($address) < 10) $errors[] = 'Alamat minimal 10 karakter.';
        if (empty($provinsi)) $errors[] = 'Provinsi harus dipilih.';
        
        // Foto validation
        $foto = $profile['foto'] ?? null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) $errors[] = 'Foto harus JPG/PNG.';
            elseif ($_FILES['foto']['size'] > 2*1024*1024) $errors[] = 'Foto maksimal 2MB.';
            else {
                $newName = uniqid('foto_', true).'.'.$ext;
                $dest = __DIR__.'/../../storage/uploads/foto/'.$newName;
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                    if (!empty($profile['foto']) && file_exists(__DIR__.'/../../'.$profile['foto'])) unlink(__DIR__.'/../../'.$profile['foto']);
                    $foto = 'storage/uploads/foto/'.$newName;
                } else $errors[] = 'Gagal upload foto.';
            }
        } elseif (empty($foto)) $errors[] = 'Foto wajib diunggah.';
        
        if (empty($errors)) {
            try {
                $exists = $profile ? true : false;
                $cols = "gender=?, phone=?, address=?, tanggal_lahir=?, last_education=?, major=?, foto=?, status_pekerjaan=?, provinsi=?, is_profile_complete=TRUE, updated_at=CURRENT_TIMESTAMP, nip=?, golongan=?, jabatan_id=?, unit_kerja_id=?";
                $params = [$gender, $phone, $address, $tanggal_lahir, $last_education, $major, $foto, $status_pekerjaan, $provinsi, $nip, $golongan, $jabatan_id, $unit_kerja_id];
                if ($exists) {
                    $db->prepare("UPDATE profiles SET $cols WHERE user_id=?")->execute([...$params, $user_id]);
                } else {
                    $db->prepare("INSERT INTO profiles (user_id,$cols) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$user_id, ...$params]);
                }
                // Save subjects
                $db->prepare("DELETE FROM user_subjects WHERE user_id=?")->execute([$user_id]);
                foreach ($mata_pelajaran as $mp) { $mp = sanitize_input($mp); if (!empty($mp)) $db->prepare("INSERT INTO user_subjects (user_id,mata_pelajaran) VALUES (?,?)")->execute([$user_id, $mp]); }
                $_SESSION['profile_complete'] = true;
                log_activity('PROFILE_UPDATE', "Profile updated", $user_id);
                // Redirect to view
                if (!headers_sent()) { header("Location: profile.php?mode=view"); exit; }
                else echo '<script>window.location="profile.php?mode=view";</script>';
            } catch (Exception $e) { $error = 'Gagal menyimpan: '.$e->getMessage(); }
        } else { $error = implode('<br>', $errors); }
    }
}

// Build form data — preserve user input on validation errors
$form = $profile ?: [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Preserve ALL POST values so form isn't lost on error
    $form['gender'] = $_POST['gender'] ?? $form['gender'] ?? '';
    $form['nip'] = $_POST['nip'] ?? $form['nip'] ?? '';
    $form['phone'] = $_POST['phone'] ?? $form['phone'] ?? '';
    $form['tanggal_lahir'] = $_POST['tanggal_lahir'] ?? $form['tanggal_lahir'] ?? '';
    $form['last_education'] = $_POST['last_education'] ?? $form['last_education'] ?? '';
    $form['major'] = $_POST['major'] ?? $form['major'] ?? '';
    $form['unit_kerja_id'] = $_POST['unit_kerja_id'] ?? $form['unit_kerja_id'] ?? '';
    $form['golongan'] = $_POST['golongan'] ?? $form['golongan'] ?? '';
    $form['jabatan_id'] = $_POST['jabatan_id'] ?? $form['jabatan_id'] ?? '';
    $form['status_pekerjaan'] = $_POST['status_pekerjaan'] ?? $form['status_pekerjaan'] ?? '';
    $form['address'] = $_POST['address'] ?? $form['address'] ?? '';
    $form['provinsi'] = $_POST['provinsi'] ?? $form['provinsi'] ?? '';
    // Preserve mapel dari POST
    if (isset($_POST['mata_pelajaran'])) {
        $user_subjects = array_map('sanitize_input', $_POST['mata_pelajaran']);
    }
}

// Check profile completeness
$is_complete = $profile && $profile['is_profile_complete'] && !empty($profile['nip']) && !empty($profile['golongan']) && $profile['unit_kerja_id'] && $profile['jabatan_id'] && !empty($profile['foto']);
if (!$is_complete && $user_role !== 'SUPERADMIN' && $mode !== 'edit') {
    $mode = 'edit';
}

$provinsi_list = ['Aceh','Sumatera Utara','Sumatera Barat','Riau','Kepulauan Riau','Jambi','Sumatera Selatan','Kepulauan Bangka Belitung','Bengkulu','Lampung','DKI Jakarta','Jawa Barat','Jawa Tengah','DI Yogyakarta','Jawa Timur','Banten','Bali','Nusa Tenggara Barat','Nusa Tenggara Timur','Kalimantan Barat','Kalimantan Tengah','Kalimantan Selatan','Kalimantan Timur','Kalimantan Utara','Sulawesi Utara','Sulawesi Tengah','Sulawesi Selatan','Sulawesi Tenggara','Gorontalo','Sulawesi Barat','Maluku','Maluku Utara','Papua Barat','Papua'];
$mata_pelajaran_options = ['Bahasa Indonesia','Bahasa Inggris','Matematika','Fisika','Kimia','Biologi','Sejarah','Ekonomi','Geografi','Sosiologi','Pendidikan Agama Islam','Pendidikan Pancasila','Informatika','Seni','Pendidikan Jasmani','Bimbingan Konseling','Bahasa Daerah','Bahasa Mandarin','Bahasa Jepang','Bahasa Jerman','Bahasa Arab','Lainnya'];

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #1a3a5c 0%, #2c5f8a 100%); color: white; padding: 20px 24px;">
            <div class="d-flex justify-content-between align-items-center">
                <div><h2 class="mb-1" style="font-size:1.4rem"><i class="fas fa-id-card me-2"></i>Profil Saya</h2><p class="mb-0 opacity-75" style="font-size:0.85rem"><?php echo $mode==='edit'?'Lengkapi data profil Anda':'Data profil Anda'; ?></p></div>
                <div>
                    <?php if ($mode === 'view'): ?><a href="?mode=edit" class="btn btn-light fw-semibold"><i class="fas fa-edit me-1"></i>Edit</a>
                    <?php else: ?><a href="?mode=view" class="btn btn-light fw-semibold"><i class="fas fa-eye me-1"></i>Lihat</a><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div><?php endif; ?>

<?php if ($mode === 'edit'): ?>
<form method="POST" enctype="multipart/form-data" id="profileForm">
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
<input type="hidden" name="update_profile" value="1">

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <div class="dashboard-card border-0 shadow-sm">
            <h5 class="mb-4 pb-2 border-bottom"><i class="fas fa-user-edit me-2 text-primary"></i>Data Pribadi</h5>

            <!-- Foto -->
            <div class="text-center mb-4">
                <div id="fotoPreview" style="width:120px;height:120px;margin:0 auto;border-radius:50%;overflow:hidden;background:#f1f5f9;display:flex;align-items:center;justify-content:center;border:3px solid #dee2e6">
                    <?php if (!empty($form['foto'])): ?><img src="<?php echo base_url($form['foto']); ?>" style="width:100%;height:100%;object-fit:cover"><?php else: ?><i class="fas fa-user" style="font-size:50px;color:#94a3b8"></i><?php endif; ?>
                </div>
                <label class="btn btn-outline-secondary btn-sm mt-2" style="cursor:pointer"><i class="fas fa-camera me-1"></i>Upload Foto<input type="file" name="foto" accept="image/*" class="d-none" onchange="previewImage(this)"></label>
                <div class="form-text">Format JPG/PNG, maks 2MB</div>
            </div>

            <div class="row g-3">
                <!-- Nama -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Nama Lengkap</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
                </div>
                <!-- NIP -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">NIP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nip" value="<?php echo htmlspecialchars($form['nip']??''); ?>" placeholder="18 digit NIP" maxlength="18" required>
                </div>
                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" readonly>
                </div>
                <!-- Telepon -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Nomor HP <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phone" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($form['phone']??''); ?>" required>
                </div>
                <!-- Gender -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select class="form-select" name="gender" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki" <?php echo ($form['gender']??'')==='Laki-laki'?'selected':''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo ($form['gender']??'')==='Perempuan'?'selected':''; ?>>Perempuan</option>
                    </select>
                </div>
                <!-- Tanggal Lahir -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="tanggal_lahir" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form['tanggal_lahir']??''); ?>" required>
                </div>
                <!-- Golongan -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Golongan <span class="text-danger">*</span></label>
                    <select class="form-select" name="golongan" required>
                        <option value="">-- Pilih Golongan --</option>
                        <?php foreach ($golongan_list as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo ($form['golongan']??'')===$g?'selected':''; ?>><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Jabatan -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Jabatan Saat Ini <span class="text-danger">*</span></label>
                    <select class="form-select" name="jabatan_id" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <?php foreach ($jabatan_list as $j): ?>
                        <option value="<?php echo $j['id']; ?>" <?php echo ($form['jabatan_id']??0)==$j['id']?'selected':''; ?>><?php echo htmlspecialchars($j['nama_jabatan'].' ('.ucfirst($j['kategori']).')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Status Pekerjaan -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Status Kepegawaian <span class="text-danger">*</span></label>
                    <select class="form-select" name="status_pekerjaan" required>
                        <option value="">-- Pilih --</option>
                        <option value="PNS" <?php echo ($form['status_pekerjaan']??'')==='PNS'?'selected':''; ?>>PNS</option>
                        <option value="PPPK" <?php echo ($form['status_pekerjaan']??'')==='PPPK'?'selected':''; ?>>PPPK</option>
                        <option value="Non-ASN" <?php echo ($form['status_pekerjaan']??'')==='Non-ASN'?'selected':''; ?>>Non-ASN</option>
                    </select>
                </div>
                <!-- Instansi / Unit Kerja -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Instansi / Unit Kerja <span class="text-danger">*</span></label>
                    <select class="form-select" name="unit_kerja_id" required>
                        <option value="">-- Pilih Unit Kerja --</option>
                        <?php foreach ($unit_list as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($form['unit_kerja_id']??0)==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['kode_satker'].' - '.$u['nama_satker']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Pendidikan -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Pendidikan Terakhir <span class="text-danger">*</span></label>
                    <select class="form-select" name="last_education" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach (['SMA/Sederajat','D3','S1/D4','S2','S3'] as $ed): ?>
                        <option value="<?php echo $ed; ?>" <?php echo ($form['last_education']??'')===$ed?'selected':''; ?>><?php echo $ed; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Program Studi -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Program Studi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="major" placeholder="Contoh: Pendidikan Matematika" value="<?php echo htmlspecialchars($form['major']??''); ?>" required>
                </div>
                <!-- Alamat -->
                <div class="col-12">
                    <label class="form-label fw-semibold small">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="address" rows="2" placeholder="Jl. Contoh No. 123, Kota" required><?php echo htmlspecialchars($form['address']??''); ?></textarea>
                </div>
                <!-- Provinsi -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold small">Provinsi <span class="text-danger">*</span></label>
                    <select class="form-select" name="provinsi" required>
                        <option value="">-- Pilih Provinsi --</option>
                        <?php foreach ($provinsi_list as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo ($form['provinsi']??'')===$p?'selected':''; ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Mata Pelajaran (jika jabatan guru) -->
            <div class="mt-4" id="mapelSection" style="display:none">
                <label class="form-label fw-semibold small">Mata Pelajaran Yang Pernah Diajar</label>
                <div class="input-group mb-2">
                    <select class="form-select" id="mapelSelect"><option value="">Pilih</option><?php foreach ($mata_pelajaran_options as $mp): ?><option value="<?php echo $mp; ?>"><?php echo $mp; ?></option><?php endforeach; ?></select>
                    <button type="button" class="btn btn-outline-secondary" onclick="addMapel()"><i class="fas fa-plus"></i></button>
                </div>
                <div id="mapelTags" class="d-flex flex-wrap gap-1">
                    <?php foreach ($user_subjects as $mp): ?>
                    <span class="badge bg-info me-1 mb-1"><?php echo htmlspecialchars($mp); ?><input type="hidden" name="mata_pelajaran[]" value="<?php echo htmlspecialchars($mp); ?>"><button type="button" class="btn-close btn-close-white ms-1" style="font-size:0.4rem" onclick="this.parentElement.remove()"></button></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary btn-lg px-4 me-2"><i class="fas fa-save me-1"></i>Simpan Profil</button>
                <a href="?mode=view" class="btn btn-outline-secondary btn-lg px-4">Batal</a>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <div class="dashboard-card border-0 shadow-sm mb-3">
            <h5 class="mb-3"><i class="fas fa-check-circle me-2 text-success"></i>Kelengkapan Data</h5>
            <?php
            $mandatory = ['NIP','Nomor HP','Tanggal Lahir','Golongan','Jabatan','Instansi','Pendidikan','Prodi','Alamat','Provinsi','Foto'];
            $filled = 0;
            $checks = [$form['nip']??null, $form['phone']??null, $form['tanggal_lahir']??null, $form['golongan']??null, ($form['jabatan_id']??null)?'OK':null, ($form['unit_kerja_id']??null)?'OK':null, $form['last_education']??null, $form['major']??null, $form['address']??null, $form['provinsi']??null, $form['foto']??null];
            foreach ($checks as $c) if (!empty($c)) $filled++;
            $pct = round($filled/count($checks)*100);
            ?>
            <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-success" style="width:<?php echo $pct; ?>%"></div></div>
            <small class="text-muted"><?php echo $filled; ?>/<?php echo count($checks); ?> field terisi (<?php echo $pct; ?>%)</small>
            <ul class="list-unstyled mt-2 small">
                <?php foreach ($mandatory as $i => $label): ?>
                <li class="mb-1"><i class="fas fa-<?php echo !empty($checks[$i])?'check-circle text-success':'circle text-muted'; ?> me-1"></i><?php echo $label; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
</form>

<?php else: ?>
<!-- VIEW MODE -->
<div class="row">
    <div class="col-lg-8">
        <div class="dashboard-card border-0 shadow-sm">
            <h5 class="mb-4 pb-2 border-bottom"><i class="fas fa-id-card me-2 text-primary"></i>Data Profil</h5>
            <div class="text-center mb-4">
                <div style="width:120px;height:120px;margin:0 auto;border-radius:50%;overflow:hidden;border:3px solid #0d6efd;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                    <?php if (!empty($form['foto'])): ?><img src="<?php echo base_url($form['foto']); ?>" style="width:100%;height:100%;object-fit:cover"><?php else: ?><i class="fas fa-user" style="font-size:50px;color:#94a3b8"></i><?php endif; ?>
                </div>
                <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h5>
                <?php if (!empty($form['nip'])): ?><small class="text-muted">NIP. <?php echo htmlspecialchars($form['nip']); ?></small><?php endif; ?>
            </div>
            <div class="row g-3">
                <?php
                $fields = [
                    ['Email', $_SESSION['user_email']],
                    ['NIP', $form['nip']??'-'],
                    ['Nomor HP', $form['phone']??'-'],
                    ['Jenis Kelamin', $form['gender']??'-'],
                    ['Tanggal Lahir', !empty($form['tanggal_lahir'])?date('d M Y',strtotime($form['tanggal_lahir'])):'-'],
                    ['Golongan', $form['golongan']??'-'],
                    ['Jabatan', (function()use($db,$form){ if(($form['jabatan_id']??0)>0){ $s=$db->prepare("SELECT nama_jabatan FROM jabatan WHERE id=?"); $s->execute([$form['jabatan_id']]); $r=$s->fetch(); return $r['nama_jabatan']??'-'; } return '-'; })()],
                    ['Status Kepegawaian', $form['status_pekerjaan']??'-'],
                    ['Instansi', (function()use($db,$form){ if(($form['unit_kerja_id']??0)>0){ $s=$db->prepare("SELECT nama_satker FROM unit_kerja WHERE id=?"); $s->execute([$form['unit_kerja_id']]); $r=$s->fetch(); return $r['nama_satker']??'-'; } return '-'; })()],
                    ['Pendidikan', $form['last_education']??'-'],
                    ['Program Studi', $form['major']??'-'],
                    ['Alamat', $form['address']??'-'],
                    ['Provinsi', $form['provinsi']??'-'],
                ];
                foreach ($fields as $f):
                    $val = is_callable($f[1]) ? $f[1]() : $f[1];
                ?>
                <div class="col-md-6"><label class="small text-muted d-block"><?php echo $f[0]; ?></label><div class="fw-semibold"><?php echo htmlspecialchars($val); ?></div></div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 pt-3 border-top">
                <a href="?mode=edit" class="btn btn-primary"><i class="fas fa-edit me-1"></i>Edit Profil</a>
                <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn btn-outline-secondary ms-2"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dashboard-card border-0 shadow-sm">
            <h5 class="mb-3"><i class="fas fa-user-check me-2 text-success"></i>Status</h5>
            <span class="badge bg-success fs-6 px-3 py-2 mb-2"><i class="fas fa-check-circle me-1"></i>PROFIL LENGKAP</span>
            <p class="text-muted small mb-0">Profil Anda sudah lengkap. Anda dapat mendaftar ujian.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.form-label.small { font-size: 0.82rem; margin-bottom: 4px; }
.form-control, .form-select { font-size: 0.9rem; }
.fw-semibold { font-weight: 600; }
</style>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function(e) { document.getElementById('fotoPreview').innerHTML = '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover">'; };
        r.readAsDataURL(input.files[0]);
    }
}
function addMapel() {
    var sel = document.getElementById('mapelSelect');
    if (!sel.value) return;
    var tags = document.getElementById('mapelTags');
    if (tags.querySelector('input[value="'+sel.value+'"]')) { alert('Sudah ada'); return; }
    var span = document.createElement('span');
    span.className = 'badge bg-info me-1 mb-1';
    span.innerHTML = sel.value+'<input type="hidden" name="mata_pelajaran[]" value="'+sel.value+'"><button type="button" class="btn-close btn-close-white ms-1" style="font-size:0.4rem" onclick="this.parentElement.remove()"></button>';
    tags.appendChild(span);
    sel.value = '';
}

// Show mapel if jabatan is guru
document.addEventListener('DOMContentLoaded', function(){
    var jabSel = document.querySelector('select[name="jabatan_id"]');
    if (jabSel) {
        jabSel.addEventListener('change', function(){
            var txt = this.options[this.selectedIndex].text.toLowerCase();
            document.getElementById('mapelSection').style.display = txt.includes('guru') ? 'block' : 'none';
        });
        // Trigger on load
        var opt = jabSel.options[jabSel.selectedIndex];
        if (opt) document.getElementById('mapelSection').style.display = opt.text.toLowerCase().includes('guru') ? 'block' : 'none';
    }
});
</script>

<?php include __DIR__ . '/../dashboard/footer-dashboard.php'; ?>